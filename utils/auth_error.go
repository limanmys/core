package utils

type AuthError struct{}

func (e *AuthError) Error() string {
	return "Unauthorized"
}

func NewAuthError() *AuthError {
	return &AuthError{}
}
