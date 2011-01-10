$Content

<% if Snippet %>
	$Snippet
	
	<% control Snippet %>
	<p><a href='$Top.Link(raw)/$Reference'>View as plain text</a></p>
	
	<% if Parent %>
		<p>Parent pastie:</p>
		<% include PastieParentList %>
	<% end_if %>
	
	<% if Children %>
		<p>Child pasties:</p>
		<% include PastieChildList %>
	<% end_if %>
	
	<% end_control %>
	
<% else %>
	<p class='message bad'>Not Found</p>
<% end_if %>
