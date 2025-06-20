# Codex API Documentation

## Overview

The Codex API provides authentication services with JWT-based token management and OAuth integration.

## Quick Start

### 1. Start the Development Server

```bash
# Start Laravel Sail (Docker containers)
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate
```

The API will be available at: `http://localhost`

### 2. Test Health Endpoint

```bash
curl -X GET http://localhost/api/health
curl -X GET http://localhost/api/ping
```

### 3. View API Documentation

The complete OpenAPI specification is available in `openapi.yaml`. You can view it with:

- **Swagger UI**: Upload `openapi.yaml` to [editor.swagger.io](https://editor.swagger.io)
- **Redoc**: Use any OpenAPI viewer
- **Postman**: Import the OpenAPI spec directly

## Available Endpoints

### Health & Monitoring
- `GET /api/health` - Comprehensive health check
- `GET /api/ping` - Simple availability check

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login with email/password
- `POST /api/auth/refresh` - Refresh access token
- `POST /api/auth/logout` - Logout and invalidate tokens

### OAuth
- `GET /api/auth/oauth/{provider}/url` - Get OAuth authorization URL
- `GET /api/oauth/{provider}/callback` - Handle OAuth callback

### User
- `GET /api/user` - Get current user (requires authentication)

## Authentication

The API uses JWT tokens with the following strategy:

- **Access Token**: 15-minute expiry, sent in `Authorization: Bearer <token>` header
- **Refresh Token**: 7-day expiry, stored in httpOnly cookie, rotated on each use

## Environment Variables

Configure these in your `.env` file:

```env
# OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/api/oauth/google/callback

GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=http://localhost/api/oauth/github/callback

# JWT Secret
JWT_SECRET=your_jwt_secret_key
```

## Testing with cURL

### Register User
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login User
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Get OAuth URL
```bash
curl -X GET http://localhost/api/auth/oauth/google/url
```

## Error Handling

All endpoints return consistent error responses:

```json
{
  "error": "Error message description"
}
```

For validation errors:

```json
{
  "error": "Validation failed",
  "messages": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

## Status Codes

- `200` - Success
- `201` - Created (registration)
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid credentials/token)
- `503` - Service Unavailable (health check failed) 