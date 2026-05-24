# Changelog

## 1.5.0

### Added
- link to LogCleaner within log report notifications

### Fixed
- some Logleaner information were written to the log file, even though it was turned off in the settings

### Changed
- language files updated
- added report interval to log report email subject
- log report emails changed from text emails to HTML emails using system email template

## 1.4.9

### Added
- display last and next report date
- sending Log report via notifications if notifications app is enabled

## 1.4.8

### Fixed
- Bug fixed: Filters do not work if Nextcloud is installed in a subdirectory ([#43](https://github.com/zomtec2311/logcleaner/issues/43)) @davie2000

## 1.4.7

### Fixed
- l10n: some language files were broken
- send log report: duplicate keys when identical email addresses are assigned multiple times for admins

## 1.4.6

### Added
- new feature sending log reports with email daily, weekly or monthly
- new settings

### Changed
- language files updated

## 1.4.5

### Changed
- language files updated

### Added
- Functionality guarantee even if some hosting providers have blocked the exec() function

## 1.4.4

### Added
- Nextcloud 34 compatibility

### Changed
- changed HTTP methode GET to POST for some routes

### Fixed
- dashboard widget not changing border color after deleting duplicates

## 1.4.3

### Fixed
- Cronjob with 0 duplicates
- dashboard widget not changing border color after deleting duplicates

## 1.4.2

### Changed
- View of entries of the desired error level
- Template for entries with the desired error level removed
- Dashboard widget now uses the new view of entries of the desired error level

## 1.4.1

### Fixed
- bug fixed minimized view on small size devices
- bug fixed page changed
- bug fixed typo in settings 'warning' was displayed as 'arning'

### Changed
- position of the minimized view

### Added
- corrupt line detection

## 1.4.0

### Changed
- Log file handling methods changed for very large files
- data collection accelerated despite new features
- language files updated
- widget code rebuilt for new methods

### Added
- Additional display of a minimized view
- new controllers with new methods for capturing very large files
- additional settings
- possible warnings about certain log file size exceedances

### Fixed
- some code cleanup
- fixed settings accordion style

## 1.3.7

### Fixed
- Fixed a bug in the settings: When settings were opened as accordion, the selected logging level was not recognizable
- Fixed a bug in output filter off: error-causing app was not displayed

## 1.3.6

### Changed
- settings for logging level: color of radio buttons

### Fixed
- Bug fixed: Blank lines in the log file caused HTTP error 500 ([#27](https://github.com/zomtec2311/logcleaner/issues/27))
- Bug fixed: Deleting duplicates did not refresh output

## 1.3.5

### Changed
- filter updated

### Added
- some css classes
- optional footer with helpful buttons

## 1.3.4

### Fixed
- dashboard widget updates when duplicates are deleted

### Added
- new feature that allows you to delete all log entries for apps that caused the log message
- new feature to filter log entries by apps that caused log messages

## 1.3.3

### Fixed
- some code cleanups

### Added
- new template that only shows entries of the desired error level

### Changed
- dashboard widget can now refer to the new template regarding the display of a certain error level

## 1.3.2

### Added
- some extra information within dashboard widget

### Fixed
- some code fixes

## 1.3.1

### Fixed
- Bug fixed when deleting the last single entry of a filtered view

### Added
- delete log entries by level within settings

### Changed
- language files updated

## 1.3.0

### Fixed
- Bug fixed duplicate handling

## 1.2.9

### Fixed
- Bug fixed wrong log output ([#18](https://github.com/zomtec2311/logcleaner/issues/18))

### Added
- Filter function for displayed error levels

### Changed
- language files updated

## 1.2.8

### Added
- Function to search for log entries on various search engines

## 1.2.7

### Fixed
- some code fixes

### Changed
- position of button 'view detail' ([#14](https://github.com/zomtec2311/logcleaner/issues/14))
- position of button 'copy to clipboard' sticky

## 1.2.6

### Added
- new function to copy log detail to clipboard

### Changed
- language files updated
- loading spinner position

## 1.2.5

### Added
- Showing log entry details

## 1.2.4

### Added
- Preparation for cooperation with AdminCockpit

## 1.2.3

### Changed
-some code fixes
-removed backup process


## 1.2.2

### Changed
- backup process

## 1.2.1

### Fixed
- new swedish language files because of bad translation (Thanks to maghog)

### Added
- backup of the previous version of the app 

## 1.2.0

### Fixed
- Bug in routes.php caused 405 error in some cases

## 1.1.9

### Added
- Nextcloud 32 compatibility

### Fixed
- Empty parameters on new installation

## 1.1.8

### Added
- New feature: you can choose how you want the settings to be displayed - accordion or modal

### Fixed
- language files updated
- code changes due to deprecated methods

## 1.1.7

### Added
- Background job: delete duplicates every 24 hours
- New feature in settings. Enable/disable background job

### Changed
- cut the title of the settings icon

### Fixed
- view for large/wide devices

## 1.1.6

### Added
- New feature in settings. Now the log level can be set without editing the config.php
- New text strings within language files

## 1.1.5

### Added
- New feature within info popover. Now you can see apps with the most log entries sorted
- New text strings within language files

### Fixed
- Bug in view for small devices

## 1.1.4

### Added
- New setting parameter for info messages
- Info messages for actions

### Changed
- language files
- controller methods
- some js code
- Changed the appearance of the info button from image to Unicode character due to path issues on some systems

### Fixed
- Bug calculating colors for the widget

## 1.1.3

### Fixed
- wrong widget img path
- wrong widget logcleaner url
- size of js-file Vue dashbord widget decreased

### Added
- folder/file icons for Vue dashboard widget
- Type of verification so that the app is only accessible to administrators

### Changed
- Design for dark themes
- Some code cleanups

### Removed
- Some dirty code that hid the app from users

## 1.1.2

### Fixed
- Now widgets are only for admins

### Added
- New Vue dashboard widget. Now you can choose between 2 WidgetItems
- New text strings within language files

## 1.1.1

### Fixed
- Improved grammar and fixed typo (Thanks to rakekniven)

### Added
- LogCleaner Dashboard Widget

### Changed
- button disabled if log file is empty

## 1.0.15

### Fixed
- Console error caused by js-Code

### Added
- New function to empty log file completely
- New text strings within language files

### Changed
- switched from NcButton to normal button due to problems in the design of the background color
- Colors in settings for some elements to make it more uniform


## 1.0.14

### Added
- some new language files AI translated:  af, ar, az, bg, bn_BD, br, ca, cs, cy, da, el, eo, es_419, es_AR, es_CL, es_CO, es_CR, es_DO, es_EC, es_GT, es_HN, es_MX, es_NI, es_PA, es_PE, es, es_PR, es_PY, es_SV, es_UY, et_EE, eu, fa, fi, ga, gl, he, hr, hu, hy, id, is, ja, ka_GE, ka, km, kn, ko, lb, lo, lt_LT, lv, mk, mn, nb, nl, pl, pt_BR, pt_PT, ro, ru, si, sk, sl, sr, sv, th, tr, ug, uk, vi, zh_CN, zh_HK, zh_TW

## 1.0.13
### Changed
- some setting code

## 1.0.12
### Fixed
- Code clean up

## 1.0.11
### Changed
- Some code cleanup

## 1.0.10
### Added
- Spinner while loading data
### Changed
- Responsive look for small devices

## 1.0.9
### Added
- Pagination

### Changed
- Path verification

## 1.0.8
### Added
- new Parameter time offset in settings
### Changed
- Settings input radio
- Settings height

## 1.0.7
### Fixed
- right date format for admin

## 1.0.6
### Fixed
- bugfix information.png

## 1.0.5
### Added
- some new features

## 1.0.4
### Changed
- added license comments to php files

## 1.0.3
### Fixed
- bug fixes

### Added
- some new settings
- some new functions
