# wrapper_tags change log

### 5.7.0 (2026-08-03)

* require Contao 5.7 and PHP 8.3+
* replace legacy content element classes with autowired fragment controllers
* replace all legacy `.html5` templates with Twig templates
* add migration from `wt_*` element types to the Contao 5.7 `wrapper_tag_*` types
* modernize service loading, DCA callbacks, backend assets, and database access
* harden tag and attribute rendering and add integration tests

### 2.1.0 (2018-02-14)

* Complete Tags content element
* minor fixes

### 2.0.0 (2018-02-09)

* possibility of adding any html attribute to html tag
* code refactoring

### 1.3.0 (2017-12-20)

* Contao 4.4+ 
* invisible wrappers do not change indent
* Validation status title is now "Validation"
* fix: translation of error: Corrupted data

### 1.2.2 (2017-12-9)

* fix: copy and move mode do not show validation, colors and indents
* fix: responsiveness of opening wrapper-tags element edit panel
* fix: colors are preserve when dragging content elements
* style attribute can be set on every html tag
* new setting: hide validation status
* full css-es in debug mode 
* installation with composer
* better readme

### 1.2.1 (2017-12-7)

* minor fixes

### 1.2.0 (2017-12-7)

* setting: colorized structure
* setting: custom html tags
* better validation
* multi elements with the same indent

### 1.1.2 (2017-11-21)

* Statuses are translated

### 1.1.0 (2017-11-18)

* More precise validation
* Refactored class, fields naming

### 1.0.1 (2017-11-17)

* Better documentation, minor fixes

### 1.0.0 (2017-11-17)

* Initial commit
