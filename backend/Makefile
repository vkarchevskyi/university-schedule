include .env
export

.PHONY: run compile test migrate-up migrate-down sqlc build up down

run:
	go run cmd/api/main.go

compile:
	go build -o bin/api cmd/api/main.go

test:
	go test -v ./...

migrate-up:
	goose -dir migrations up

migrate-down:
	goose -dir migrations down

sqlc:
	sqlc generate

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down
