$Content

<h3>Latest Snippets</h3>
<% if LatestSnippets %>
	<ul>
		<% control LatestSnippets %>
			<li><a href="$Top.Link(show)/$Reference"><% include PastieSnippetTitle %></a></li>
		<% end_control %>
	</ul>
<% else %>
	<p><em>No snippets found!</em></p>
<% end_if %>