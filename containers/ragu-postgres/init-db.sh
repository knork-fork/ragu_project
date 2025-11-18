#!/usr/bin/env bash

if [[ $1 = "local" ]]
then
  db_name="ragu_db"
elif [[ $1 = "test" ]]
then
  db_name="ragu_db_test"
else
  echo 'Missing environment! "local" or "test"?'
  exit 1
fi

# End all processes that are using the database before attempting to drop the database
docker exec -e PGPASSWORD=ragu_pass -i ragu-postgres psql -h localhost -U ragu_user postgres -c \
    "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$db_name' AND pid <> pg_backend_pid();"

# Drop database if it existed
docker exec -e PGPASSWORD=ragu_pass -i ragu-postgres psql -h localhost -U ragu_user -d postgres -c "DROP DATABASE IF EXISTS $db_name"
if [[ $? -ne 0 ]]
then
    echo "Cannot drop database $db_name. Aborting..."
    exit 2
fi

# Create database
docker exec -e PGPASSWORD=ragu_pass -i ragu-postgres psql -h localhost -U ragu_user -d postgres -c "CREATE DATABASE $db_name";
if [[ $? -ne 0 ]]
then
    echo "Failed creating database $db_name."
    exit 2
fi

# Import dump
docker exec -e PGPASSWORD=ragu_pass -i ragu-postgres \
    sh -c "psql -h localhost -U ragu_user -d \"$db_name\" < /application/db_skeleton.sql"
if [[ $? -ne 0 ]]
then
    echo "Failed importing database dump to $db_name."
    exit 2
fi

# Run fixtures (if test)
if [[ $1 = "test" ]]
then
    echo "Running test fixtures..."
fi

echo "Database $db_name imported."