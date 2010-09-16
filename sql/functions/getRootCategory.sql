/*
 * Returns the category-id of the root category from a descendant.
 *
 * @param_id INTEGER: the id of the category to search from.
 *
 * @returned_row INTEGER: the category id
 */

CREATE OR REPLACE FUNCTION getRootCategory(INTEGER) RETURNS INTEGER AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_parent INTEGER;
		local_id INTEGER;
	BEGIN
		-- get current category results
		SELECT INTO local_parent parent
		FROM Category
		WHERE id = param_id;

		IF local_parent IS NULL THEN
			RETURN param_id;
		END IF;


		-- get parent category results
		WHILE local_parent IS NOT NULL LOOP
			BEGIN
				local_id = local_parent;

				SELECT INTO local_parent parent
				FROM Category
				WHERE id = local_parent;
			END;
		END LOOP;

		RETURN local_id;
	END;
$$ LANGUAGE 'plpgsql';
