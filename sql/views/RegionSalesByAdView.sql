create or replace view RegionSalesByAdView as

select Region.id as region, Ad.id as ad,
-- {{{ day sales
(select sum(item_total)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '1 day')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as day_sales,
-- }}}
-- {{{ day orders
(select count(Orders.id)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '1 day')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as day_orders,
-- }}}
-- {{{ week sales
(select sum(item_total)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '1 week')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as week_sales,
-- }}}
-- {{{ week orders
(select count(Orders.id)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '1 week')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as week_orders,
-- }}}
-- {{{ two week sales
(select sum(item_total)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '2 week')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as two_week_sales,
-- }}}
-- {{{ two week orders
(select count(Orders.id)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '2 week')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as two_week_orders,
-- }}}
-- {{{ month sales
(select sum(item_total)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '1 month')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as month_sales,
-- }}}
-- {{{ month orders
(select count(Orders.id)
from Orders
inner join Locale on Orders.locale = Locale.id
where Orders.createdate > (LOCALTIMESTAMP - interval '1 month')
	and Locale.region = Region.id
	and Orders.ad = Ad.id
) as month_orders,
-- }}}
-- {{{ total sales
(select sum(item_total)
from Orders
inner join Locale on Orders.locale = Locale.id
where Locale.region = Region.id
	and Orders.ad = Ad.id
) as total_sales,
-- }}}
-- {{{ total orders
(select count(Orders.id)
from Orders
inner join Locale on Orders.locale = Locale.id
where Locale.region = Region.id
	and Orders.ad = Ad.id
) as total_orders
-- }}}

from Region
inner join Ad on Region.id = Region.id;
