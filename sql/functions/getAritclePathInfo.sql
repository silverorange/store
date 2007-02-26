/*
 * Returns path information for an article.
 *
 * @param_id INTEGER: the id of the article.
 *
 * @returned_row type_article_path_info: a row containing id, parent, shortname, title
 *
 * Returns a set of returned_rows ordered from leaf to root.
 * If the article is not found, returns an empty recordset
 */
CREATE TYPE type_article_path_info AS (id INTEGER, parent INTEGER, shortname VARCHAR(255), title VARCHAR(255));

CREATE OR REPLACE FUNCTION getArticlePathInfo(INTEGER) RETURNS SETOF type_article_path_info AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_id INTEGER;
		returned_row type_article_path_info%ROWTYPE;
	BEGIN
		local_id := param_id;

		WHILE local_id is not null LOOP
			BEGIN
				SELECT INTO returned_row id, parent, shortname, title
				FROM Article
				WHERE id = local_id;

				-- return the row
				IF FOUND THEN
					RETURN NEXT returned_row;
				END IF;

				-- move up the tree
				local_id := returned_row.parent;
			END;
		END LOOP;

		RETURN;
	END;
$$ LANGUAGE 'plpgsql';
