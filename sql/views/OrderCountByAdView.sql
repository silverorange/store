-- Selects order count and conversion rate for an ad for Veseys.
-- Conversion rate is returned as null if no conversions have been made.
create or replace view OrderCountByAdView as
    select
		Ad.id as ad,
		sum(AdLocaleBinding.total_orders) as order_count,
		case when sum(Ad.total_referrers) = 0 then
			null
		else
			cast(sum(AdLocaleBinding.total_orders) as float) /
			cast(Ad.total_referrers as float) end as conversion_rate
    from AdLocaleBinding
		inner join Ad on AdLocaleBinding.ad = Ad.id
    group by Ad.id, Ad.total_referrers;
