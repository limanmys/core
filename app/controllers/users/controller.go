package users

import (
	"strconv"
	"time"

	"github.com/gofiber/fiber/v3"
	"go.uber.org/zap"

	"github.com/limanmys/core/app/entities"
	"github.com/limanmys/core/internal/services"
	"github.com/limanmys/core/internal/validation"
)

// Controller handles user-related HTTP requests
type Controller struct {
	userService services.UserService
	logger      *zap.Logger
}

// NewController creates a new user controller
func NewController(userService services.UserService, logger *zap.Logger) *Controller {
	return &Controller{
		userService: userService,
		logger:      logger,
	}
}

type LoginRequest struct {
	Username string `json:"username" validate:"required"`
	Password string `json:"password" validate:"required"`
}

// Login handles user authentication
func (ctrl *Controller) Login(c fiber.Ctx) error {
	var payload *LoginRequest
	if err := c.Bind().JSON(&payload); err != nil {
		ctrl.logger.Error("Failed to bind login request", zap.Error(err))
		return err
	}

	if err := validation.Validate(payload); err != nil {
		ctrl.logger.Error("Login validation failed", zap.Error(err))
		return err
	}

	_, token, err := ctrl.userService.Authenticate(payload.Username, payload.Password)
	if err != nil {
		ctrl.logger.Warn("Login attempt failed", zap.String("username", payload.Username))
		return err
	}

	// Add Http-Only cookie to response
	c.Cookie(&fiber.Cookie{
		Name:     "token",
		Value:    token,
		HTTPOnly: true,
		Expires:  time.Now().Add(4 * time.Hour), // 4 hours
	})

	ctrl.logger.Info("User logged in successfully", zap.String("username", payload.Username))
	return c.JSON(fiber.Map{"token": token})
}

// CurrentUser returns the current authenticated user
func (ctrl *Controller) CurrentUser(c fiber.Ctx) error {
	return c.JSON(c.Locals("user").(*entities.User))
}

// Index returns paginated list of users with search functionality
func (ctrl *Controller) Index(c fiber.Ctx) error {
	page, _ := strconv.Atoi(c.Query("page", "1"))
	perPage, _ := strconv.Atoi(c.Query("per_page", "25"))
	search := c.Query("search", "")

	users, total, err := ctrl.userService.ListWithPagination(page, perPage, search)
	if err != nil {
		ctrl.logger.Error("Failed to get users", zap.Error(err))
		return err
	}

	// Create paginated response
	totalPages := int((total + int64(perPage) - 1) / int64(perPage))
	response := map[string]interface{}{
		"total_records": total,
		"records":       users,
		"current_page":  page,
		"total_pages":   totalPages,
	}

	return c.JSON(response)
}

// Show returns a specific user
func (ctrl *Controller) Show(c fiber.Ctx) error {
	idStr := c.Params("id")
	id, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		ctrl.logger.Error("Invalid user ID", zap.String("id", idStr))
		return fiber.NewError(fiber.StatusBadRequest, "Invalid user ID")
	}

	user, err := ctrl.userService.GetByID(uint(id))
	if err != nil {
		ctrl.logger.Error("Failed to find user", zap.String("id", idStr), zap.Error(err))
		return err
	}

	return c.JSON(user)
}

// Create creates a new user
func (ctrl *Controller) Create(c fiber.Ctx) error {
	var payload *entities.User
	if err := c.Bind().JSON(&payload); err != nil {
		ctrl.logger.Error("Failed to bind create user request", zap.Error(err))
		return err
	}

	if err := ctrl.userService.Create(payload); err != nil {
		ctrl.logger.Error("Failed to create user", zap.Error(err))
		return err
	}

	ctrl.logger.Info("User created successfully", zap.String("username", payload.Username))
	return c.JSON(payload)
}

// Update updates an existing user
func (ctrl *Controller) Update(c fiber.Ctx) error {
	idStr := c.Params("id")
	id, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		ctrl.logger.Error("Invalid user ID", zap.String("id", idStr))
		return fiber.NewError(fiber.StatusBadRequest, "Invalid user ID")
	}

	var payload *entities.User
	if err := c.Bind().JSON(&payload); err != nil {
		ctrl.logger.Error("Failed to bind update user request", zap.Error(err))
		return err
	}

	if err := ctrl.userService.Update(uint(id), payload); err != nil {
		ctrl.logger.Error("Failed to update user", zap.String("id", idStr), zap.Error(err))
		return err
	}

	ctrl.logger.Info("User updated successfully", zap.String("id", idStr))
	return c.JSON(payload)
}

// Delete deletes a user
func (ctrl *Controller) Delete(c fiber.Ctx) error {
	idStr := c.Params("id")
	id, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		ctrl.logger.Error("Invalid user ID", zap.String("id", idStr))
		return fiber.NewError(fiber.StatusBadRequest, "Invalid user ID")
	}

	if err := ctrl.userService.Delete(uint(id)); err != nil {
		ctrl.logger.Error("Failed to delete user", zap.String("id", idStr), zap.Error(err))
		return err
	}

	ctrl.logger.Info("User deleted successfully", zap.String("id", idStr))
	return c.SendStatus(fiber.StatusNoContent)
}
