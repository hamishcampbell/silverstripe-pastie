$Content

<% if Snippet %>
	$Snippet
	
	<% control Snippet %>
		<p><a href='$Top.Link(raw)/$Reference'>View as plain text</a></p>
		
		<% if Parent %>
			<p><% _t('PARENT', 'Parent') %> $SingularName:</p>
			<% include PastieParentList %>
		<% end_if %>
		
		<% if Children %>
			<p><% _t('CHILD', 'Child') %> $PluralName:</p>
			<% include PastieChildList %>
		<% end_if %>
	<% end_control %>
	
<% else %>
	<p><em><% _t('NOTFOUND', 'Snippet not found') %></em></p>
<% end_if %>
