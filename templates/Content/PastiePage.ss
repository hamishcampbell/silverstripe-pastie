$Content

<h3><% _t('LATESTSNIPPETS', 'Latest Snippets') %></h3>
<% if LatestSnippets %>
	<ul>
		<% control LatestSnippets %>
			<li><a href="$Top.Link(show)/$Reference"><% include PastieSnippetTitle %></a></li>
		<% end_control %>
	</ul>
<% else %>
	<p><em><% _t('NOSNIPPETSFOUND', 'No recent Snippets found.') %></em></p>
<% end_if %>