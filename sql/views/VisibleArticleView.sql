create or replace view VisibleArticleView as
	select id, region, instance
		from Article
			inner join ArticleRegionBinding on
				Article.id = ArticleRegionBinding.article
			left outer join ArticleInstanceBinding on
				Article.id = ArticleInstanceBinding.article
		where visible = true;
