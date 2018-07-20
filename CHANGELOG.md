# Change Log

## [Unreleased]

## RELEASE 0.5.0 - 2018-07-20
### Changed
- Technical - Use the "official" domain for the default server host.
- Performance - Improve the speed of listing the records by executing their count into another request.

### Fixed
- Related Data - Fix the related data count value in the context of a search.

## RELEASE 0.4.0 - 2018-05-11
### Added
- Custom Search - Developers can restrict the search to specific collection fields.

### Changed
- Readme - Add some documentation about configuration cache.
- Smart Actions - Developers can customize Smart Actions with a new API (closer to the other lianas implementation).

## RELEASE 0.3.0 - 2018-04-03
### Added
- Related Data - Delete records directly from a hasMany listing.

### Fixed
- Related Data - Fix potential issue on record dissociations.

## RELEASE 0.2.1 - 2018-02-21
### Added
- Filters - Add a new "is after X hours ago" operator to filter on date fields.

## RELEASE 0.2.0 - 2018-01-11
### Added
- Authentication - Users can connect to their project using Google Single Sign-On.

### Fixed
- CORS - Update the CORS module to be compatible with Laravel >=5.5.0.

## RELEASE 0.1.5 - 2017-12-27
### Changed
- Performance - Reduce drastically the number of CORS preflight requests send by the API clients.

## RELEASE 0.1.4 - 2017-12-01
### Fixed
- Collection Names - Make the transition smooth between old and new collection name.

## RELEASE 0.1.3 - 2017-11-15
### Fixed
- BelongsTo - Fix "belongsTo" associations having a parent primary key different than "id".
- Collections - Fix collection access for models having an unconventional table name.
- Record Destroy - Prevent a potential issue on record deletion.

## RELEASE 0.1.2 - 2017-09-10
### Changed
- Laravel - Support Laravel 5.5.

## RELEASE 0.1.1 - 2017-08-23
### Fixed
- Exports - Fix bad initial implementation for exports authentication.

## RELEASE 0.1.0 - 2017-08-21
### Added
- Installation - Auto-register the service provider on Laravel 5.5+.
- Search - Split "simple" and "deep" search features with a new param.
- Exports - Forest can now handle large data exports.

## RELEASE 0.0.26 - 2017-07-12
### Added
- Debug Mode - Add debug logs for the apimap creation.
- Onboarding - Add specific error logs for bad configurations.

### Changed
- Apimap - Generate the models names based on the database table names instead of the class names.

## RELEASE 0.0.24 - 2017-07-11
### Added
- Search - Users can search on the hasMany associated data of a specific record.

## RELEASE 0.0.23 - 2017-07-05
### Added
- Filters - Add the before x hours operator.

## RELEASE 0.0.22 - 2017-05-30
### Added
- Filters - Add the not contains operator.

## RELEASE 0.0.21 - 2016-04-06
### Added
- Smart Actions - Users don't have to select records to use a smart action through the global option.
- Version Warning - Display a warning message if the liana version used is too old.

## RELEASE 0.0.20 - 2016-03-27
### Fixed
- Records Search - Prevent an internal error if a hasOne relationship model is not found during a search.

## RELEASE 0.0.19 - 2016-03-26
### Fixed
- Records Search - Fix records search on MySql while adding an existing record in a hasMany relationship.

## RELEASE 0.0.18 - 2016-03-24
### Fixed
- Record Getter - Prevent an unexpected error if the record does not exist.
- Records Getter - Fix records retrieval for resources having a hasOne relationship.

## RELEASE 0.0.17 - 2016-02-24
### Fixed
- Laravel 5.4.+ - Support Laravel version 5.4.+.

## RELEASE 0.0.16 - 2016-02-07
### Added
- Smart Action - Smart actions support.

## RELEASE 0.0.15 - 2016-02-07
### Fixed
- Collections - Fix the collections names formatting.
- Search - Fix the search on MySQL projects.

## RELEASE 0.0.14 - 2016-01-31
### Changed
- Relationships - Relationship methods having parameters are now ignored.

## RELEASE 0.0.13 - 2016-01-29
### Fixed
- Relationships - Support relationship methods having parameters.

## RELEASE 0.0.12 - 2016-01-27
### Changed
- Packages - Support GuzzleHttp 5+ and 6+.

## RELEASE 0.0.11 - 2016-01-25
### Fixed
- Packages - Rollback constraint on GuzzleHttp.

## RELEASE 0.0.10 - 2016-01-25
### Fixed
- Packages - Reduce constraint on GuzzleHttp.

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
