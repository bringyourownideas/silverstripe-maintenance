<strong class="package-summary__title">$Title.XML</strong>
<span
    class="package-summary__details"
    id="package-summary__details-{$ID}"
    data-description="$Description.ATT"
>
    <%-- Contents are rendered via SiteSummary.js in React --%>
</span>

<% loop $Badges %>
    <span class="package-summary__badge badge<% if $Type %> badge-$Type.ATT<% end_if %>">$Title.XML</span>
<% end_loop %>
