package search

import (
	"strings"

	"gorm.io/gorm"
)

func Search(query string, tx *gorm.DB) {
	words := strings.Fields(query)
	err := tx.Statement.Parse(tx.Statement.Model)
	if err != nil {
		return
	}
	fields := []string{}
	if tx.Statement.Table != "" {
		fields = append(fields, "\""+tx.Statement.Table+"\".*")
	}
	for _, join := range tx.Statement.Joins {
		if strings.Contains(join.Name, "JOIN") {
			continue
		}
		fields = append(fields, "\""+join.Name+"\".*")
	}
	concat := strings.Join(fields, ", ' ',")

	// Create a new transaction scope for the search conditions
	tx.Where(func(db *gorm.DB) *gorm.DB {
		subQuery := db.Where("CONCAT("+concat+") ilike ?", "%"+query+"%")
		for _, word := range words {
			subQuery = subQuery.Or("CONCAT("+concat+") ilike ?", "%"+word+"%")
		}
		return subQuery
	})
}
