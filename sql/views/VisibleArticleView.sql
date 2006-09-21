create or replace view VisibleArticleView as
	select id, region
		from Article
			inner join ArticleRegionBinding on
				Article.id = ArticleRegionBinding.article
		where show = true;
