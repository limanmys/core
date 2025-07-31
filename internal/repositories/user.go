package repositories

import (
	"gorm.io/gorm"

	"github.com/limanmys/core/app/entities"
)

// UserRepository handles user database operations
type UserRepository interface {
	GetByUsername(username string) (*entities.User, error)
	GetByID(id uint) (*entities.User, error)
	Create(user *entities.User) error
	Update(id uint, user *entities.User) error
	Delete(id uint) error
	List(offset, limit int) ([]*entities.User, int64, error)
	ListWithSearch(offset, limit int, search string) ([]*entities.User, int64, error)
	Count() (int64, error)
}

// userRepository implements UserRepository interface
type userRepository struct {
	db *gorm.DB
}

// NewUserRepository creates a new user repository
func NewUserRepository(db *gorm.DB) UserRepository {
	return &userRepository{
		db: db,
	}
}

// GetByUsername gets user by username
func (r *userRepository) GetByUsername(username string) (*entities.User, error) {
	var user *entities.User
	if err := r.db.Where("username = ?", username).First(&user).Error; err != nil {
		return nil, err
	}
	return user, nil
}

// GetByID gets user by ID
func (r *userRepository) GetByID(id uint) (*entities.User, error) {
	var user *entities.User
	if err := r.db.Where("id = ?", id).First(&user).Error; err != nil {
		return nil, err
	}
	return user, nil
}

// Create creates a new user
func (r *userRepository) Create(user *entities.User) error {
	return r.db.Create(user).Error
}

// Update updates a user
func (r *userRepository) Update(id uint, user *entities.User) error {
	return r.db.Model(&entities.User{}).Where("id = ?", id).Updates(user).Error
}

// Delete deletes a user
func (r *userRepository) Delete(id uint) error {
	return r.db.Delete(&entities.User{}, id).Error
}

// List returns paginated users
func (r *userRepository) List(offset, limit int) ([]*entities.User, int64, error) {
	var users []*entities.User
	var count int64

	// Count total records
	if err := r.db.Model(&entities.User{}).Count(&count).Error; err != nil {
		return nil, 0, err
	}

	// Get paginated records
	if err := r.db.Omit("password").Offset(offset).Limit(limit).Find(&users).Error; err != nil {
		return nil, 0, err
	}

	return users, count, nil
}

// ListWithSearch returns paginated users with search functionality
func (r *userRepository) ListWithSearch(offset, limit int, search string) ([]*entities.User, int64, error) {
	var users []*entities.User
	var count int64

	query := r.db.Model(&entities.User{})

	// Apply search if provided
	if search != "" {
		searchPattern := "%" + search + "%"
		query = query.Where("LOWER(username) LIKE LOWER(?) OR LOWER(first_name) LIKE LOWER(?) OR LOWER(last_name) LIKE LOWER(?) OR LOWER(email) LIKE LOWER(?)",
			searchPattern, searchPattern, searchPattern, searchPattern)
	}

	// Count total records with search
	if err := query.Count(&count).Error; err != nil {
		return nil, 0, err
	}

	// Get paginated records with search
	if err := query.Omit("password").Offset(offset).Limit(limit).Find(&users).Error; err != nil {
		return nil, 0, err
	}

	return users, count, nil
}

// Count returns total user count
func (r *userRepository) Count() (int64, error) {
	var count int64
	if err := r.db.Model(&entities.User{}).Count(&count).Error; err != nil {
		return 0, err
	}
	return count, nil
}
