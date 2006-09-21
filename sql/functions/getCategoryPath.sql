/*
 * Returns the path string of an category.
 *
 * @param_parent INTEGER: the id of the article to search from.
 *
 * Returns a VARCHAR containing the path string for the given article. If the
 * article does not exist, NULL is returned.
 */
CREATE OR REPLACE FUNCTION getCategoryPath(INTEGER) RETURNS VARCHAR(255) AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_parent INTEGER;
		local_shortname VARCHAR(255);
		local_path VARCHAR(255);
	BEGIN
		local_path = NULL; 

		-- get current category results
		SELECT INTO local_parent, local_shortname parent, shortname
		FROM Category
		WHERE id = param_id;

		IF FOUND THEN
			local_path = local_shortname;
		END IF;

		-- get parent category results
		WHILE local_parent IS NOT NULL LOOP
			BEGIN

				SELECT INTO local_parent, local_shortname parent, shortname
				FROM Category
				WHERE id = local_parent;

				IF FOUND THEN
					IF local_path IS NULL THEN
						local_path = local_shortname;
					ELSE
						local_path = local_shortname || '/' || local_path;
					END IF;
				END IF;

			END;
		END LOOP;

		RETURN local_path;
	END;
$$ LANGUAGE 'plpgsql';
