create or replace view OrderCommissionTotalView as
select orders.id as ordernum, item_total as commission_total
from Orders;
