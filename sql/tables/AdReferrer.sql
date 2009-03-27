alter table AdReferrer add aggregated boolean not null default false;
CREATE INDEX AdReferrer_aggregated_index ON AdReferrer(aggregated);
