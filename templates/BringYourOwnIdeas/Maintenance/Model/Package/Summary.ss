<strong class="package-summary__title">$Title.XML</strong>
<span
    class="package-summary__details-container"
    id="package-summary__details-{$ID}"
    data-description="$Description.ATT"
    data-link="https://addons.silverstripe.org/add-ons/$Name.ATT"
    data-link-title="<%t BringYourOwnIdeas\\Maintenance\\Reports\\SiteSummary.AddonsLinkTitle "View {package} on addons.silverstripe.org" package=$Title.ATT %>"
>
    <%-- Contents are rendered via SiteSummary.js in React --%>
</span>

<% loop $Badges %>
    <span class="package-summary__badge badge<% if $Type %> badge-$Type.ATT<% end_if %>">$Title.XML</span>
<% end_loop %>
