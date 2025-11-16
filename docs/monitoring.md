### Monitoring

| Component       | What it does                      | Why you’d want it                                                                        |
| --------------- | --------------------------------- | ---------------------------------------------------------------------------------------- |
| **Uptime Kuma** | Web-based uptime monitor          | Pings your endpoints (HTTP, TCP, ping) and alerts you if they go down.                   |
| **Prometheus**  | Time-series database for metrics  | Collects numeric metrics (CPU, RAM, latency, etc.) from exporters like Kuma or cAdvisor. |
| **Grafana**     | Dashboard & visualization UI      | Reads metrics from Prometheus and shows them as graphs, gauges, and alerts.              |
| **cAdvisor**    | Docker container metrics exporter | Feeds Prometheus per-container CPU/memory/network usage automatically.                   |

### GPU

By default docker-composer.yml has `runtime: nvidia`.
This requires NVIDIA drivers and NVIDIA Container Toolkit to be installed on the host machine.

First verify that the NVIDIA drivers are installed correctly:

```bash
nvidia-smi
```

Then install the NVIDIA Container Toolkit:
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y nvidia-container-toolkit
sudo nvidia-ctk runtime configure --runtime=docker
sudo systemctl restart docker
```

You may have to manually add a repository for nvidia-container-toolkit.

### Update

Currently using beszel and uptime kuma.