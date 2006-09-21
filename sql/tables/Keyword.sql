create table Keyword (
	id serial,
	keywords varchar(300),
	primary key (id)
);

CREATE INDEX Keyword_keywords_index ON Keyword(keywords);
