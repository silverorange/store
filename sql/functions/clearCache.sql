CREATE OR REPLACE FUNCTION clearCache () RETURNS INTEGER AS $$
	BEGIN
		truncate VisibleProductCache;
		insert into VisibleProductCache
			select * from VisibleProductView;

		truncate CategoryVisibleProductCountByRegionCache;
		insert into CategoryVisibleProductCountByRegionCache
			select *  from CategoryVisibleProductCountByRegionView  where category is not null;

		truncate CategoryVisibleMajorProductCountByRegionCache;
		insert into CategoryVisibleMajorProductCountByRegionCache
			select *  from CategoryVisibleMajorProductCountByRegionView  where category is not null;

		RETURN 0;
	END;
$$ LANGUAGE 'plpgsql';
