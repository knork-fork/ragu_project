<script setup>
import { onMounted, onBeforeUnmount, ref, watch, nextTick, computed } from 'vue'

/* ---------- PROPS ---------- */
const props = defineProps({
  apiBase: { type: String, required: true },
  apiPath: { type: String, default: '/chat/completions' },
  apiKey:  { type: String, default: '' },
  model:   { type: String, default: 'your-rag-model' },
  headers: { type: Object, default: () => ({}) },
  systemPrompt: { type: String, default: '' },
  placeholder:  { type: String, default: '' },
  stream:       { type: Boolean, default: true },

  /* profile */
  profileName:  { type: String, default: 'Anonymous' },
  profileEmail: { type: String, default: '' },
  profileAvatar:{ type: String, default: '' },
})

const emit = defineEmits(['action', 'error', 'modeChange', 'logout'])

/* ---------- SESSIONS ---------- */
const STORAGE_KEY = 'rtc.sessions.v1'
const sessions = ref([])
const currentId = ref(null)
const messages = ref([])

function nano() { return Math.random().toString(36).slice(2) }
function saveSessions(){ try{ localStorage.setItem(STORAGE_KEY, JSON.stringify(sessions.value)) }catch{} }
function bindMessages(){ const s=sessions.value.find(x=>x.id===currentId.value); messages.value = s ? s.messages : [] }
function selectSession(id){ currentId.value = id; bindMessages() }
function newSession(title){
  const s={ id:nano(), title:title||'New Chat', createdAt:Date.now(), messages:[] }
  sessions.value.unshift(s); selectSession(s.id)
  if (props.systemPrompt) add('system', props.systemPrompt)
}
function deleteSession(id){
  const i=sessions.value.findIndex(s=>s.id===id)
  if(i!==-1){ sessions.value.splice(i,1); if(currentId.value===id){ currentId.value=sessions.value[0]?.id||null; bindMessages() } }
}
function renameSession(id,title){ const s=sessions.value.find(x=>x.id===id); if(s) s.title=title }

onMounted(()=>{
  try{ const raw=localStorage.getItem(STORAGE_KEY); if(raw) sessions.value = JSON.parse(raw)||[] }catch{}
  if(!sessions.value.length) newSession('New Chat'); else { currentId.value = sessions.value[0].id; bindMessages() }
})
watch(sessions, saveSessions, { deep:true })

/* ---------- CHAT ---------- */
const input=ref(''); const sending=ref(false); const logRef=ref(null)
function add(role,content){ messages.value.push({ id:nano(), role, content }) }
function scrollToBottom(){ nextTick(()=>{ const el=logRef.value; if(el) el.scrollTo({ top: el.scrollHeight, behavior:'smooth' }) }) }
watch(messages, scrollToBottom)

/* mode switch: PROMPT / SAVE (A/B) */
const mode = ref('prompt')
function setMode(next){
  if (mode.value === next) return
  mode.value = next
  emit('modeChange', mode.value)
  emit('action', 'mode:' + mode.value)
}

/* ---------- TOOLS: MODELS + RAG ---------- */
const modelOptions = [
  'Qwen3:4b-Instruct',
  'Qwen3:4b',
  'Qwen3-vl:4b',
  'gemma3:4b-it-qat',
  'wizard-vicuna-uncensored:7b',
  'gemma3:27b-it-qat'
]
const selectedModel = ref(
  modelOptions.includes(props.model) ? props.model : 'Qwen3:4b-Instruct'
)

const ragEnabled = ref(false)
const ragTopic   = ref('')
const topicOptions = ['unsorted', 'work-related', 'car details', 'DIY projects']

/* Autocomplete state */
const topicOpen = ref(false)
const topicWrap = ref(null)
const dropdownEl = ref(null)
const dropdownStyle = ref({})

/* Filtered topics */
const filteredTopics = computed(() => {
  const q = (ragTopic.value || '').toLowerCase().trim()
  if (!q) return topicOptions
  return topicOptions
    .map(t => ({ t, score: similarityScore(q, t.toLowerCase()) }))
    .sort((a,b)=>b.score-a.score)
    .map(x=>x.t)
})
function similarityScore(q, s){
  const a=[...new Set(q.split(''))], b=[...new Set(s.split(''))]
  const inter=a.filter(ch=>b.includes(ch)).length
  return inter / Math.max(1, Math.min(a.length,b.length))
}
function chooseTopic(t){ ragTopic.value=t; closeTopicDropdown() }

/* Positioning: flip above if not enough space below */
function updateDropdownPos() {
  if (!topicWrap.value) return
  const pad = 6
  const rect = topicWrap.value.getBoundingClientRect()

  // try to use actual dropdown height; fall back to target height
  const measuredH = dropdownEl.value?.offsetHeight || 180
  const spaceBelow = window.innerHeight - rect.bottom
  const spaceAbove = rect.top

  const openAbove = spaceBelow < measuredH && spaceAbove > spaceBelow

  let top
  let available
  if (openAbove) {
    top = rect.top - measuredH - pad
    available = rect.top - pad
  } else {
    top = rect.bottom + pad
    available = window.innerHeight - rect.bottom - pad
  }

  const maxH = Math.max(100, Math.min(180, available))

  dropdownStyle.value = {
    position: 'fixed',
    top: `${Math.max(8, top)}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
    zIndex: 9999,
    maxHeight: `${maxH}px`,
    overflowY: 'auto'
  }
}

/* Open/close with listeners + outside click */
const onReposition = () => updateDropdownPos()
const onDocClick = (e) => {
  const wrap = topicWrap.value
  const drop = dropdownEl.value
  if (!wrap) return
  if (wrap.contains(e.target) || (drop && drop.contains(e.target))) return
  closeTopicDropdown()
}

function openTopicDropdown() {
  if (topicOpen.value) { nextTick(updateDropdownPos); return }
  topicOpen.value = true
  nextTick(updateDropdownPos)
  window.addEventListener('scroll', onReposition, { capture: true, passive: true })
  window.addEventListener('resize', onReposition, { passive: true })
  document.addEventListener('mousedown', onDocClick, true)
}
function closeTopicDropdown() {
  if (!topicOpen.value) return
  topicOpen.value = false
  window.removeEventListener('scroll', onReposition, true)
  window.removeEventListener('resize', onReposition, true)
  document.removeEventListener('mousedown', onDocClick, true)
}
onBeforeUnmount(closeTopicDropdown)

/* Keep position fresh while typing */
watch(ragTopic, () => nextTick(updateDropdownPos))

/* ---------- SEND ---------- */
async function send(){
  const content=(input.value||'').trim(); if(!content||sending.value) return

  if (mode.value === 'save') {
    add('user', content)
    add('system', '[saved] note captured')
    input.value = ''
    return
  }

  add('user', content); input.value=''; sending.value=true

  const s=sessions.value.find(x=>x.id===currentId.value)
  if(s && (s.title==='New Chat'||s.title==='Untitled')) renameSession(s.id, content.slice(0,40))

  try{
    const body={
      model: selectedModel.value || props.model || 'your-rag-model',
      messages: messages.value.filter(m=>m.role!=='system').map(m=>({ role:m.role, content:m.content })),
      stream: !!props.stream,
      tools: { rag: { enabled: ragEnabled.value, topic: ragTopic.value || null } }
    }
    const res=await fetch((props.apiBase.replace(/\/$/,''))+props.apiPath,{
      method:'POST',
      headers:Object.assign({ 'content-type':'application/json' }, props.apiKey?{authorization:'Bearer '+props.apiKey}:{}, props.headers||{}),
      body:JSON.stringify(body)
    })

    if(!props.stream || !res.body || !res.body.getReader){
      const j=await res.json().catch(()=>({}))
      const text=(j?.choices?.[0]?.message?.content) || String(j||'')
      add('assistant', text); sending.value=false; return
    }

    const reader=res.body.getReader(); let acc=''; const assistantId=nano()
    messages.value.push({ id:assistantId, role:'assistant', content:'' })
    while(true){
      const chunk=await reader.read(); if(chunk.done) break
      const decoded=new TextDecoder().decode(chunk.value,{stream:true}); acc+=decoded
      const parts=acc.split('\n'); acc=parts.pop()||''
      for(let i=0;i<parts.length;i++){
        const line=(parts[i]||'').trim(); if(!line) continue
        if(line.indexOf('data:')===0){
          const payload=line.slice(5).trim(); if(payload==='[DONE]') continue
          let delta=''; try{
            const j=JSON.parse(payload)
            delta=(j?.choices?.[0]?.delta?.content) ||
                  (j?.choices?.[0]?.message?.content) ||
                  j.content || ''
          }catch(e){ delta=line+'\n' }
          const msg=messages.value.find(m=>m.id===assistantId); if(msg && delta) msg.content+=delta
        } else {
          const msg=messages.value.find(m=>m.id===assistantId); if(msg) msg.content+=line+'\n'
        }
      }
    }
  }catch(err){
    emit('error', err); add('assistant','[error] '+(err?.message ?? String(err)))
  }finally{ sending.value=false }
}

/* ---------- PROFILE ---------- */
const initials = computed(()=>{
  const n = (props.profileName||'').trim()
  if(!n) return 'U'
  const parts = n.split(/\s+/).slice(0,2)
  return parts.map(p=>p[0]?.toUpperCase()||'').join('')
})
const showMenu = ref(false)
function toggleMenu(){ showMenu.value = !showMenu.value }
function logout() {
  // @logout in App.vue (calls handleLogout)
  emit('logout')
  showMenu.value = false
}
function settings(){ emit('action','settings'); showMenu.value=false }
</script>

<template>
  <div class="rt-root">
    <!-- SIDEBAR -->
    <aside class="rt-sidebar">
      <div class="rt-sb-head">
        <div class="rt-sb-title">SESSIONS</div>
        <button class="rt-newbtn" @click.stop.prevent="newSession()">+ NEW</button>
      </div>

      <div class="rt-sb-list">
        <button
          v-for="s in sessions"
          :key="s.id"
          class="rt-sb-item"
          :class="{ active: s.id === currentId }"
          @click="selectSession(s.id)"
          @dblclick="renameSession(s.id, prompt('Rename session', s.title) || s.title)"
        >
          <div class="rt-sb-name">{{ s.title || 'Untitled' }}</div>
          <div class="rt-sb-sub">{{ new Date(s.createdAt).toLocaleString() }}</div>
          <div class="rt-sb-actions">
            <span class="rt-sb-dot"></span>
            <button class="rt-x" title="Delete" @click.stop="deleteSession(s.id)">×</button>
          </div>
        </button>
      </div>

      <!-- TOOL PANELS -->
      <div class="rt-tools">
        <!-- MODELS -->
        <section class="rt-tool-card">
          <header class="rt-tool-head">
            <span class="rt-tool-title">Models</span>
          </header>
          <div class="rt-tool-body">
            <div class="rt-select">
              <select v-model="selectedModel" aria-label="model select">
                <option v-for="m in modelOptions" :key="m" :value="m">{{ m }}</option>
              </select>
              <span class="rt-select-caret">▾</span>
            </div>
          </div>
        </section>

        <!-- RAG -->
        <section class="rt-tool-card">
        <header class="rt-tool-head rt-tool-head--split">
            <span class="rt-tool-title">RAG</span>
            <button
            class="rt-switch"
            :class="{ on: ragEnabled }"
            @click="ragEnabled = !ragEnabled"
            :aria-pressed="ragEnabled ? 'true' : 'false'"
            title="Toggle RAG"
            >
            <span class="rt-switch-knob"></span>
            </button>
        </header>

        <div class="rt-tool-body">
            <!-- Topic input ONLY -->
            <div class="rt-topicwrap" ref="topicWrap" @keydown.escape="closeTopicDropdown">
            <input
                class="rt-topic-input"
                v-model="ragTopic"
                placeholder="Topic…"
                @focus="openTopicDropdown"
                @input="openTopicDropdown"
                @blur="closeTopicDropdown"
            />
            </div>

            <Teleport to="body">
            <div
                v-if="topicOpen"
                ref="dropdownEl"
                class="rt-autocomplete"
                :style="dropdownStyle"
            >
                <button
                v-for="t in filteredTopics"
                :key="t"
                class="rt-ac-item"
                @mousedown.prevent="chooseTopic(t)"
                >
                {{ t }}
                </button>
            </div>
            </Teleport>
        </div>
        </section>

      </div>

      <!-- PROFILE FOOTER -->
      <div class="rt-sb-profile">
        <div class="rt-prof">
          <div class="rt-prof-avatar">
            <img v-if="props.profileAvatar" :src="props.profileAvatar" alt="avatar" />
            <div v-else class="rt-prof-initials">{{ initials }}</div>
          </div>
          <div class="rt-prof-meta">
            <div class="rt-prof-name">{{ props.profileName }}</div>
            <div class="rt-prof-email" v-if="props.profileEmail">{{ props.profileEmail }}</div>
          </div>
          <button class="rt-ham" title="menu" @click="toggleMenu">≡</button>
          <div v-if="showMenu" class="rt-menu">
            <button @click="settings">SETTINGS</button>
            <button @click="logout">LOG OUT</button>
          </div>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <div class="rt-main">
      <header class="rt-topbar">
        <div class="rt-brand">
          <span class="rt-badge">◤</span> RAGu // Knowledge Terminal
          <span class="rt-dot"></span>
        </div>

        <div class="rt-toggle" role="tablist" aria-label="mode switch">
          <button
            class="rt-pill"
            :class="{ active: mode === 'prompt' }"
            @click="setMode('prompt')"
            role="tab"
            aria-selected="mode==='prompt'"
            title="PROMPT mode"
          >
            A ▸ PROMPT
          </button>
          <button
            class="rt-pill"
            :class="{ active: mode === 'save' }"
            @click="setMode('save')"
            role="tab"
            aria-selected="mode==='save'"
            title="SAVE mode"
          >
            B ▸ SAVE
          </button>
        </div>
      </header>

      <div class="rt-scanlines"></div>
      <div class="rt-grid"></div>

      <main ref="logRef" class="rt-log">
        <template v-for="m in messages" :key="m.id">
          <div class="rt-line" :class="'rt-'+m.role">
            <span class="rt-tag">[{{ m.role.toUpperCase() }}]</span>
            <pre class="rt-msg">{{ m.content }}</pre>
          </div>
        </template>
      </main>

      <footer class="rt-composer">
        <div class="rt-inputwrap rt-input-full">
          <span class="rt-caret">></span>
          <input
            :placeholder="props.placeholder || (mode==='save' ? 'TYPE A NOTE…' : 'TYPE YOUR QUERY…')"
            v-model="input"
            @keydown.enter.prevent="send"
            class="rt-input"
          />
          <button class="rt-sendbtn" :disabled="sending" @click="send" title="Send">▶</button>
        </div>
      </footer>
    </div>
  </div>
</template>

<style>
:root{
  --ui-bg:#0a0f14; --ui-surface:#0f1822; --ui-grid:#10202c;
  --ui-text:#b9f0ff; --ui-muted:#7ea9b6; --ui-accent:#5ee7ff; --ui-warn:#ffd76a;
  --ui-user:#ffb38a; --ui-assistant:#b2ffb0; --ui-border:#1c3240;
  --ui-glow:0 0 10px rgba(94,231,255,.35);
  --ui-font: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "IBM Plex Mono", monospace;
}
</style>

<style scoped>
.rt-root {
  --ui-bg:#0a0f14; --ui-surface:#0f1822; --ui-grid:#10202c;
  --ui-text:#b9f0ff; --ui-muted:#7ea9b6; --ui-accent:#5ee7ff; --ui-warn:#ffd76a;
  --ui-user:#ffb38a; --ui-assistant:#b2ffb0; --ui-border:#1c3240;
  --ui-glow:0 0 10px rgba(94,231,255,.35);
  --ui-font: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "IBM Plex Mono", monospace;
}

/* Layout */
.rt-root { position:relative; display:grid; grid-template-columns:300px 1fr; grid-template-rows:100vh; background:var(--ui-bg); color:var(--ui-text); font-family:var(--ui-font); }
.rt-sidebar { display:grid; grid-template-rows:auto 1fr auto; gap:10px; padding:12px; border-right:1px solid var(--ui-border); background:linear-gradient(180deg,#0d1620,#0a1119); position:relative; z-index:30; }
.rt-main { position:relative; display:grid; grid-template-rows:auto 1fr auto; background:radial-gradient(1200px 600px at 70% -20%,#132333 0%,#0a0f14 60%,#05090d 100%); z-index:1; }

/* Sidebar header */
.rt-sb-head{ display:flex; align-items:center; justify-content:space-between; position:relative; z-index:35; }
.rt-sb-title{ letter-spacing:1px; font-weight:700; color:var(--ui-muted); }
.rt-newbtn{ background:#0d1a24; border:1px solid var(--ui-border); color:var(--ui-text); padding:6px 10px; border-radius:10px; cursor:pointer; font-weight:700; letter-spacing:.5px; box-shadow: inset 0 0 0 1px rgba(94,231,255,.06), var(--ui-glow); position:relative; z-index:36; }
.rt-newbtn:hover{ filter:brightness(1.1); }
.rt-newbtn:active{ border-color:var(--ui-accent); box-shadow:0 0 8px rgba(94,231,255,.25), var(--ui-glow); }

.rt-sb-list{ display:flex; flex-direction:column; gap:8px; overflow:auto; }
.rt-sb-item{ display:grid; grid-template-columns:1fr auto; gap:6px; align-items:center; background:#0d1a24; border:1px solid var(--ui-border); padding:10px; border-radius:8px; cursor:pointer; text-align:left; color:var(--ui-text); }
.rt-sb-item.active{ border-color:var(--ui-accent); box-shadow:var(--ui-glow); }
.rt-sb-name{ font-weight:600; }
.rt-sb-sub{ font-size:11px; color:var(--ui-muted); }
.rt-sb-actions{ display:flex; align-items:center; gap:6px; }
.rt-sb-dot{ width:6px; height:6px; border-radius:50%; background:var(--ui-accent); box-shadow:0 0 10px var(--ui-accent); }
.rt-x{ background:transparent; border:1px solid var(--ui-border); color:var(--ui-text); border-radius:4px; padding:0 6px; cursor:pointer; }

/* Tools */
.rt-tools{ display:flex; flex-direction:column; gap:12px; margin-top:8px; }
.rt-tool-card{ background:#0d1a24; border:1px solid var(--ui-border); border-radius:10px; box-shadow:inset 0 0 0 1px rgba(94,231,255,.05); overflow:hidden; }
.rt-tool-head{ display:flex; align-items:center; justify-content:space-between; padding:8px 10px; border-bottom:1px solid var(--ui-border); background:linear-gradient(180deg,#0f1c27,#0d1520); }
.rt-tool-head{
  border-top-left-radius: inherit;
  border-top-right-radius: inherit;
  background:linear-gradient(180deg,#0f1c27,#0d1520);
  border-bottom:1px solid var(--ui-border);
}
.rt-tool-title{ font-weight:700; color:var(--ui-muted); letter-spacing:.5px; }
.rt-tool-body{ display:flex; flex-direction:column; gap:8px; padding:10px; }

/* Model select */
.rt-select{ position:relative; width:100%; }
.rt-select select{ width:100%; box-sizing:border-box; background:#0b1620; color:var(--ui-text); border:1px solid var(--ui-border); border-radius:8px; padding:9px 28px 9px 10px; outline:none; font-family:var(--ui-font); font-size:13px; }
.rt-select select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: none;
  background-color: #0b1620; /* keep your bg */
}

.rt-select-caret{ position:absolute; right:8px; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--ui-muted); }

/* put switch on the same line as the title */
.rt-tool-head--split {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Switch (flex layout to center the knob perfectly) */
.rt-switch{
  display:inline-flex;
  align-items:center;
  justify-content:flex-start;     /* knob left by default */
  width:48px;
  height:24px;
  padding:2px;                    /* gives room for knob; handles the border visually */
  box-sizing:border-box;          /* fixes off-by-1 from borders */
  border-radius:999px;
  border:1px solid var(--ui-border);
  background:#0b1620;
  cursor:pointer;
  transition:background .2s ease, border-color .2s ease, box-shadow .2s ease;
  box-shadow:inset 0 0 0 1px rgba(94,231,255,.06);
}
.rt-switch.on{
  justify-content:flex-end;       /* knob slides right */
  background:#113043;
  border-color:var(--ui-accent);
  box-shadow:0 0 10px rgba(94,231,255,.25);
}
.rt-switch-knob{
  width:18px;
  height:18px;
  border-radius:999px;
  background:#25b6d2;            /* readable on dark bg */
  transition:transform .2s ease, background .2s ease;
}

/* Topic input (exact fit) */
.rt-topicwrap{ width:100%; position:relative; }
.rt-topic-input{
  display:block; width:100%; max-width:100%; box-sizing:border-box;
  background:#0b1620; border:1px solid var(--ui-border); border-radius:8px;
  padding:9px 10px; color:var(--ui-text); font-family:var(--ui-font); font-size:13px; outline:none;
  box-shadow:inset 0 0 0 1px rgba(94,231,255,.05);
}
.rt-topic-input::placeholder{ color:var(--ui-muted); opacity:.7; }

/* Floating list (teleported) */
.rt-autocomplete{
  position:fixed;
  background:#0b1620; border:1px solid var(--ui-border);
  border-radius:8px; box-shadow:var(--ui-glow);
  overflow:hidden; z-index:9999;
}
.rt-ac-item{ display:block; width:100%; text-align:left; background:transparent; border:none; color:var(--ui-text); padding:8px 10px; cursor:pointer; font-family:var(--ui-font); font-size:13px; }
.rt-ac-item:hover{ background:#0e1c26; }

/* Profile */
.rt-sb-profile{ border-top:1px solid var(--ui-border); padding-top:10px; position:relative; }
.rt-prof{ display:grid; grid-template-columns:auto 1fr auto; gap:10px; align-items:center; }
.rt-prof-avatar{ position:relative; width:36px; height:36px; border-radius:9999px; background:#0d1a24; border:1px solid var(--ui-border); display:grid; place-items:center; overflow:hidden; box-shadow:var(--ui-glow); }
.rt-prof-avatar img{ width:100%; height:100%; object-fit:cover; image-rendering:pixelated; }
.rt-prof-initials{ font-weight:700; color:var(--ui-text); }
.rt-prof-status{ position:absolute; right:-1px; bottom:-1px; width:10px; height:10px; border-radius:9999px; border:2px solid #0d1a24; }
.rt-prof-meta{ display:flex; flex-direction:column; min-width:0; }
.rt-prof-name{ font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rt-prof-email{ font-size:11px; color:var(--ui-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rt-ham{ background:#0d1a24; border:1px solid var(--ui-border); color:var(--ui-text); border-radius:6px; padding:4px 8px; cursor:pointer; box-shadow:inset 0 0 0 1px rgba(94,231,255,.06); position:relative; z-index:15; }
.rt-menu{ position:absolute; right:0; bottom:42px; display:flex; flex-direction:column; background:#0b1620; border:1px solid var(--ui-border); border-radius:8px; box-shadow:var(--ui-glow); overflow:hidden; z-index:20; }
.rt-menu button{ background:transparent; border:none; color:var(--ui-text); padding:8px 12px; text-align:left; cursor:pointer; }
.rt-menu button:hover{ background:#0e1c26; }

/* Topbar + mode switch */
.rt-topbar{ display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:linear-gradient(180deg,#112233,#0b1520); border-bottom:1px solid var(--ui-border); box-shadow:var(--ui-glow); }
.rt-brand{ display:flex; align-items:center; gap:10px; font-weight:600; letter-spacing:1px; }
.rt-badge{ color: var(--ui-warn); }
.rt-dot{ width:7px; height:7px; border-radius:50%; background:var(--ui-accent); box-shadow:0 0 12px var(--ui-accent); margin-left:8px; animation: ping 2.4s infinite ease-in-out; }
@keyframes ping { 0%{transform:scale(.8);opacity:.9} 100%{transform:scale(1.8);opacity:.1} }
.rt-toggle{ display:inline-flex; gap:6px; background:#0d1a24; border:1px solid var(--ui-border); padding:4px; border-radius:10px; box-shadow:inset 0 0 0 1px rgba(94,231,255,.06), var(--ui-glow); }
.rt-pill{ background:transparent; border:1px solid transparent; color:var(--ui-muted); padding:6px 10px; border-radius:8px; cursor:pointer; font-weight:700; letter-spacing:.5px; }
.rt-pill.active{ color:var(--ui-text); border-color:var(--ui-accent); box-shadow:0 0 8px rgba(94,231,255,.25); }

/* Log + composer */
.rt-scanlines{ pointer-events:none; position:absolute; inset:0; background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px); background-size:100% 2px; mix-blend-mode:soft-light; opacity:.35; }
.rt-grid{ pointer-events:none; position:absolute; inset:0; background:linear-gradient(to right,var(--ui-grid) 1px,transparent 1px),linear-gradient(to bottom,var(--ui-grid) 1px,transparent 1px); background-size:24px 24px; mask-image:radial-gradient(ellipse at center,black 60%,transparent 100%); opacity:.35; }
.rt-log{ position:relative; padding:16px; overflow:auto; display:flex; flex-direction:column; gap:12px; }
.rt-line{ display:grid; grid-template-columns:auto 1fr; align-items:start; gap:10px; background:rgba(13,26,36,.6); border:1px solid var(--ui-border); border-left:4px solid var(--ui-grid); padding:10px 12px; border-radius:10px; }
.rt-line.rt-user{border-left-color:var(--ui-user);} .rt-line.rt-assistant{border-left-color:var(--ui-assistant);} .rt-line.rt-system{border-left-color:var(--ui-warn); opacity:.9}
.rt-tag{ color:var(--ui-muted); min-width:90px; }
.rt-msg{ margin:0; white-space:pre-wrap; word-break:break-word; font-variant-ligatures:none; image-rendering:pixelated; }
.rt-composer{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-top:1px solid var(--ui-border); background:linear-gradient(180deg,#0b1520,#09121a); }
.rt-inputwrap{ display:grid; grid-template-columns:14px 1fr auto; align-items:center; gap:8px; background:#0d1a24; border:1px solid var(--ui-border); border-radius:8px; padding:10px; box-shadow:inset 0 0 0 1px rgba(94,231,255,.05); }
.rt-input-full{ width:100%; }
.rt-caret{ color:var(--ui-accent); }
.rt-input{ background:transparent; border:none; outline:none; color:var(--ui-text); font-family:var(--ui-font); letter-spacing:.3px; }
.rt-sendbtn{ background:#0d1a24; border:1px solid var(--ui-border); color:var(--ui-text); border-radius:6px; padding:6px 10px; cursor:pointer; box-shadow:inset 0 0 0 1px rgba(94,231,255,.06); }
.rt-sendbtn:disabled{ opacity:.5; cursor:not-allowed; }

@media (max-width:900px){
  .rt-root{ grid-template-columns:0 1fr; }
  .rt-sidebar{ display:none; }
}
</style>