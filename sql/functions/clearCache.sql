CREATE OR REPLACE FUNCTION clearCache () RETURNS INTEGER AS $$
	BEGIN
		truncate VisibleProductCache;
		insert into VisibleProductCache
			select * from VisibleProductView;

		truncate CategoryAvailableProductCountByRegionCache;
		insert into CategoryAvailableProductCountByRegionCache
			select *  from CategoryAvailableProductCountByRegionView  where category is not null;

		truncate CategoryVisibleProductCountByRegionCache;
		insert into CategoryVisibleProductCountByRegionCache
			select *  from CategoryVisibleProductCountByRegionView  where category is not null;

		truncate CategoryVisibleMajorProductCountByRegionCache;
		insert into CategoryVisibleMajorProductCountByRegionCache
			select *  from CategoryVisibleMajorProductCountByRegionView  where category is not null;

		RETURN 0;
	END;
$$ LANGUAGE 'plpgsql';
