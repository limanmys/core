# Build stage
FROM golang:1.23-alpine AS builder

# Install git and ca-certificates
RUN apk add --no-cache git ca-certificates

# Set working directory
WORKDIR /app

# Copy go mod and sum files
COPY go.mod go.sum ./

# Download dependencies
RUN go mod download

# Copy source code
COPY . .

# Build the application
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o main cmd/server/main.go

# Final stage
FROM alpine:latest

# Install ca-certificates for HTTPS
RUN apk --no-cache add ca-certificates

# Create non-root user
RUN addgroup -g 1001 -S app && \
    adduser -S app -u 1001

WORKDIR /root/

# Copy the binary from builder
COPY --from=builder /app/main .

# Change ownership to app user
RUN chown -R app:app /root
USER app

# Expose port
EXPOSE 2878

# Run the application
CMD ["./main"]
