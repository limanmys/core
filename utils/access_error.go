package utils

type AccessError struct{}

func (e *AccessError) Error() string {
	return "Access Denied"
}

func NewAccessError() *AccessError {
	return &AccessError{}
}
