<% if Title %>
	<span class='pastie-title'>$Title</span>
<% else %>
	<span class='pastie-title pastie-title-untitled'><% _t('UNTITLED', 'Untitled') %></span>
<% end_if %>
&mdash; 
<span class='pastie-created-ago'><% _t('CREATED', 'Created') %> $Created.Ago</span> <% _t('BY', 'by') %>  
<span class='pastie-created-author'><% if Owner %>$Owner.Name<% else %><em>unknown</em><% end_if %></span> 
&mdash;  
<span class='pastie-language'>$Language.XML</span>
