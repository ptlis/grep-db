
# 0.1.1 - 2019-02-08

* Remove accidentally committed debug code.

# 0.1.0 - 2019-02-08

* Total rethink of approach; almost everything has changed.
* Added tests.


# 0.0.11 - 2017-09-20

* Retrieve additional metadata for columns (whether it is nullable and whether it is indexed).
* Remove needless DISTINCT clauses when building column metadata. This resolves errors when processing a column without a primary key.


# 0.0.10 - 2017-09-20

* Fix listing of tables; Add constraint to ensure we only deal with 'real' tables, excluding things like views.


# 0.0.9 - 2017-08-22

* Fix charset handling.


# 0.0.8 - 2017-08-12

* Rework replace methods to use yield to allow incremental replacmenets.


# 0.0.7 - 2017-08-08

* Add (optional) offset & limit when searching tables.
* Add ability to return a specified range when searching.


# 0.0.6 - 2017-08-06

* Fix retrieval of tables - restrict to selected database.


# 0.0.5 - 2017-08-06

* Expose server metadata from GrepDb.


# 0.0.4 - 2017-08-06

* Add getters to Server & Database metadata to retrieve database & table names.


# 0.0.3 - 2017-08-06

* Rework database and table metadata to be built lazily.


# 0.0.2 - 2017-08-06

* Rework library to work across all database on a server.


# 0.0.1 - 2017-08-03

* Initial implementation of basic search & search and replace functionality.
