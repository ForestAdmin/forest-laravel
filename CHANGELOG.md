# Change Log

## [Unreleased]

## RELEASE 0.0.9 - 2016-12-04
### Fixed
- Charts - Fix a regression that broke charts using MySQL.

## RELEASE 0.0.8 - 2016-12-03
### Fixed
- Record Update - Do not set date fields if the value sent is null.
- Record Update - Update the updated_at timestamp on record update.

## RELEASE 0.0.7 - 2016-12-02
### Added
- Pie Charts - Support group by on belongsTo associations.

## RELEASE 0.0.6 - 2016-12-02
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

## RELEASE 0.0.3 - 2016-11-18
### Fixed
- Serialization - Fix the serialization regression due to a typo on a class name.

## RELEASE 0.0.2 - 2016-11-16
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
