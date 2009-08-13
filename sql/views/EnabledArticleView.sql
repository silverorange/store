create or replace view EnabledArticleView as
	select id, region
		from Article
			inner join ArticleRegionBinding on
				Article.id = ArticleRegionBinding.article;
