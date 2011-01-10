<% if Title %>
	<span class='pastie-title'>$Title</span>
<% else %>
	<span class='pastie-title pastie-title-untitled'>Untitled</span>
<% end_if %>
&mdash; 
<span class='pastie-created-ago'>Created $Created.Ago</span> by 
<span class='pastie-created-author'><% if Owner %>$Owner.Name<% else %><em>unknown</em><% end_if %></span> 
&mdash;  
<span class='pastie-language'>$Language.XML</span>
