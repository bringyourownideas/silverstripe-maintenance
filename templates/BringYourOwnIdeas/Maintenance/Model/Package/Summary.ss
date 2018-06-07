<strong class="package-summary__title">$Title.XML</strong>
<% loop $Badges %>
    <span class="package-summary__badge badge<% if $Type %> badge-$Type.ATT<% end_if %>">$Title.XML</span>
<% end_loop %>
<span
    class="package-summary__details-container"
    id="package-summary__details-{$ID}"
    data-schema="$DataSchema.JSON"
>
    <%-- Contents are rendered via SiteSummary.js in React --%>
</span>
