# Change Log

## [Unreleased]
### Added
- Chart Filters - Support chart filters on belongsTo associations.

### Fixed
- Charts - Fix potential ambiguous aggregation field.

## RELEASE 0.0.5 - 2016-12-02
### Added
- Line Charts - Support line charts using MySQL databases.

## RELEASE 0.0.4 - 2016-12-01
### Fixed
- HasOne Associations - Fix the retrieval of collections having hasOne associations.

## RELEASE 0.0.3 - 2016-11-18
### Added
- Charts - Support all kind of charts (value, pie, line).
- Search - Support search in the records lists.
- Filters - Support all kind of filters for segments and charts.
- Associations - Support belongsTo/hasOne/hasMany/belongsToMany associations edition.
- Pagination - Support the pagination.
- Sorting - Support sorting.

### Changed
- Apimap - Change the command to send the apimap (php artisan forest:send-apimap).
- Configuration - Change configuration variables names.
- CORS - The package does not need to require manually barryvdh/laravel-cors package.
