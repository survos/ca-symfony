# database must be the MySQL one
dbname=ca_demo_4
# sudo mysql | CREATE USER 'main'@'localhost' IDENTIFIED BY 'main';
# GRANT ALL PRIVILEGES ON *.* TO 'mainc'@'localhost';
bin/console doctrine:database:create --if-not-exists
mysql -u main -pmain $dbname < ~/data/ca_demo_3.dump

bin/console --env=main doctrine:query:sql "TRUNCATE TABLE ca_change_log_snapshots"
bin/console --env=main doctrine:query:sql "TRUNCATE TABLE ca_change_log_subjects"
bin/console --env=main doctrine:query:sql "DELETE FROM ca_change_log"
#bin/console --env=main doctrine:query:sql "TRUNCATE TABLE ca_change_log"
bin/console --env=main doctrine:query:sql "TRUNCATE TABLE ca_sql_search_word_index"
bin/console --env=main doctrine:query:sql "TRUNCATE TABLE ca_sql_search_words"
mysqldump -u main -pmain $dbname > ~/data/main.dump
ls -lh ~/data/main.dump

mysql -u gpw2ao470x04u1pb -pop2hs6xw7dd23zhc -h "j21q532mu148i8ms.cbetxkdyhwsb.us-east-1.rds.amazonaws.com" xn8t75ddlwyfcrhs < ~/data/main.dump
#mysql -u caherokudemo -pcaherokudemo -h db4free.net caherokudemo < ~/data/main.dump

bin/console doctrine:database:import ~/data/main.dump
exit 1;

mysql -u skepv1e2m8k1i9og -pp04i27fm4yvv9xpj -h "cis9cbtgerlk68wl.cbetxkdyhwsb.us-east-1.rds.amazonaws.com" fzozhtv1vkrv75w8 < ~/data/main.dump
#mysql -u main -pmain $dbname < ~/data/ca_demo_3.dump
#mysql -u main -p $dbname < ~/data/seth.sql

bin/console    doctrine:query:sql "CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, raw_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', xml LONGTEXT NOT NULL, mde_count INT DEFAULT NULL, ui_count INT DEFAULT NULL, list_count INT DEFAULT NULL, info_url VARCHAR(255) DEFAULT NULL, display_count INT DEFAULT NULL, UNIQUE INDEX UNIQ_8157AA0F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB"
bin/console    doctrine:query:sql "CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB"

## add auto-increment for doctrine inspection.  Or make these part of the migration?
bin/console     doctrine:query:sql "ALTER TABLE ca_application_vars ADD id INT PRIMARY KEY AUTO_INCREMENT"
bin/console    doctrine:query:sql "ALTER TABLE ca_change_log_snapshots ADD id INT PRIMARY KEY AUTO_INCREMENT"
bin/console    doctrine:query:sql "ALTER TABLE ca_change_log_subjects ADD id INT PRIMARY KEY AUTO_INCREMENT"
bin/console    doctrine:query:sql "ALTER TABLE ca_eventlog ADD id INT PRIMARY KEY AUTO_INCREMENT"
bin/console    doctrine:query:sql "ALTER TABLE ca_media_content_locations ADD id INT PRIMARY KEY AUTO_INCREMENT"
bin/console    doctrine:query:sql "ALTER TABLE ca_schema_updates ADD id INT PRIMARY KEY AUTO_INCREMENT"
bin/console    doctrine:query:sql "ALTER TABLE ca_sql_search_ngrams ADD id INT PRIMARY KEY AUTO_INCREMENT"

bin/console    doctrine:query:sql "ALTER TABLE ca_collections_x_storage_locations DROP CONSTRAINT ca_collections_x_storage_locations_label_left_id"
bin/console    doctrine:query:sql "ALTER TABLE ca_collections_x_storage_locations DROP CONSTRAINT ca_collections_x_storage_locations_label_right_id"


# now switch database to postgres
#pgloader mysql://main:main@127.0.0.1:3306/$dbname postgresql://main:main@127.0.0.1:5432/$dbname

# now we can generate the classes.

#bin/console    doctrine:query:sql "select tbl.table_schema,
#       tbl.table_name
#from information_schema.tables tbl
#where table_type = 'BASE TABLE'
#  and table_schema not in ('pg_catalog', 'information_schema')
#  and not exists (select 1
#                  from information_schema.key_column_usage kcu
#                  where kcu.table_name = tbl.table_name
#                    and kcu.table_schema = tbl.table_schema)"
