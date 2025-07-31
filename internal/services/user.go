package services

import (
	"golang.org/x/crypto/bcrypt"

	"github.com/limanmys/core/app/entities"
	"github.com/limanmys/core/internal/repositories"
	"github.com/limanmys/core/utils"
)

// UserService handles user business logic
type UserService interface {
	Authenticate(username, password string) (*entities.User, string, error)
	GetByID(id uint) (*entities.User, error)
	GetByUsername(username string) (*entities.User, error)
	Create(user *entities.User) error
	Update(id uint, user *entities.User) error
	Delete(id uint) error
	List(offset, limit int) ([]*entities.User, int64, error)
	ListWithPagination(page, perPage int, search string) ([]*entities.User, int64, error)
}

// userService implements UserService interface
type userService struct {
	userRepo repositories.UserRepository
}

// NewUserService creates a new user service
func NewUserService(userRepo repositories.UserRepository) UserService {
	return &userService{
		userRepo: userRepo,
	}
}

// Authenticate authenticates a user and returns token
func (s *userService) Authenticate(username, password string) (*entities.User, string, error) {
	user, err := s.userRepo.GetByUsername(username)
	if err != nil {
		return nil, "", utils.NewAuthError()
	}

	if !s.checkPasswordHash(password, user.Password) {
		return nil, "", utils.NewAuthError()
	}

	token, err := utils.CreateToken(user.Username, user.ID)
	if err != nil {
		return nil, "", err
	}

	return user, token, nil
}

// GetByID gets user by ID
func (s *userService) GetByID(id uint) (*entities.User, error) {
	return s.userRepo.GetByID(id)
}

// GetByUsername gets user by username
func (s *userService) GetByUsername(username string) (*entities.User, error) {
	return s.userRepo.GetByUsername(username)
}

// Create creates a new user
func (s *userService) Create(user *entities.User) error {
	hashedPassword, err := s.createHash(user.Password)
	if err != nil {
		return err
	}
	user.Password = hashedPassword
	return s.userRepo.Create(user)
}

// Update updates a user
func (s *userService) Update(id uint, user *entities.User) error {
	if user.Password != "" {
		hashedPassword, err := s.createHash(user.Password)
		if err != nil {
			return err
		}
		user.Password = hashedPassword
	}
	return s.userRepo.Update(id, user)
}

// Delete deletes a user
func (s *userService) Delete(id uint) error {
	return s.userRepo.Delete(id)
}

// List returns paginated users
func (s *userService) List(offset, limit int) ([]*entities.User, int64, error) {
	return s.userRepo.List(offset, limit)
}

// ListWithPagination returns paginated users with search functionality
func (s *userService) ListWithPagination(page, perPage int, search string) ([]*entities.User, int64, error) {
	offset := (page - 1) * perPage

	if search != "" {
		return s.userRepo.ListWithSearch(offset, perPage, search)
	}

	return s.userRepo.List(offset, perPage)
}

// createHash creates a bcrypt hash from password
func (s *userService) createHash(password string) (string, error) {
	bytes, err := bcrypt.GenerateFromPassword([]byte(password), 14)
	return string(bytes), err
}

// checkPasswordHash checks if password matches hash
func (s *userService) checkPasswordHash(password, hash string) bool {
	return bcrypt.CompareHashAndPassword([]byte(hash), []byte(password)) == nil
}
