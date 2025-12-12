# Google OAuth Setup Instructions

## Steps to enable Google Login:

### 1. Create Google Cloud Project
1. Go to https://console.cloud.google.com/
2. Create a new project or select an existing one
3. Enable the Google+ API

### 2. Create OAuth 2.0 Credentials
1. Go to **APIs & Services** > **Credentials**
2. Click **Create Credentials** > **OAuth client ID**
3. Choose **Web application**
4. Add Authorized redirect URIs:
   - For local development: `http://localhost:8000/auth/connect/google/check`
   - For production: `https://yourdomain.com/auth/connect/google/check`
5. Save and copy your **Client ID** and **Client Secret**

### 3. Configure Your Application
1. Open `.env` file in your project root
2. Replace the placeholders with your actual credentials:
```
GOOGLE_CLIENT_ID=your_actual_client_id_here
GOOGLE_CLIENT_SECRET=your_actual_client_secret_here
```

### 4. Test the Integration
1. Start your Symfony server: `symfony server:start`
2. Go to http://localhost:8000/auth/login
3. Click "Sign in with Google"
4. You should be redirected to Google's login page
5. After successful authentication, you'll be redirected back to your app

## How it Works:
- When a user clicks "Sign in with Google", they are redirected to Google's OAuth consent screen
- After granting permission, Google redirects back to `/auth/connect/google/check`
- The app fetches user information from Google (email, name)
- If the user doesn't exist, a new account is created automatically
- If the user exists, they are logged in
- Cookies are set to maintain the session

## Security Notes:
- Never commit `.env` with real credentials to version control
- Use `.env.local` for local development credentials
- For production, use environment variables or a secure secrets management system
