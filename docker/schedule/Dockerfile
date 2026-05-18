FROM golang:1.26-alpine AS base

WORKDIR /app

FROM base AS deps

COPY services/schedule/go.mod services/schedule/go.sum ./
RUN go mod download

FROM deps AS dev

COPY services/schedule/ .
EXPOSE 8081
CMD ["go", "run", "."]

FROM deps AS build

COPY services/schedule/ .
RUN CGO_ENABLED=0 GOOS=linux go build -o /usr/local/bin/schedule-service .

FROM alpine:3.22 AS prod

RUN addgroup -S app && adduser -S app -G app

COPY --from=build /usr/local/bin/schedule-service /usr/local/bin/schedule-service

USER app
EXPOSE 8081

CMD ["/usr/local/bin/schedule-service"]
