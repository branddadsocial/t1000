#!/usr/bin/env python3
"""Build BrandDad SEO ecosystem: topic packs, MU, oneshots, ownership map."""
from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent
ECO = ROOT / "branddad-seo-ecosystem"
DATA = ECO / "data"
AB_DATA = ROOT / "branddad-autoblog" / "data"
SITES = AB_DATA / "sites"


def slugify(s: str) -> str:
    s = re.sub(r"[^a-z0-9]+", "-", s.lower()).strip("-")
    return s[:72]


def topic(**opts):
    title = opts["title"]
    return {
        "id": opts["id"],
        "cluster": opts.get("cluster", "general"),
        "content_style": opts.get("content_style", "search_traffic"),
        "status": "queued",
        "primary_keyword": opts["primary_keyword"],
        "secondary_keywords": opts.get("secondary_keywords") or [],
        "title": title,
        "slug": opts.get("slug") or slugify(title),
        "angle": opts["angle"],
        "buyer_intent": opts.get("buyer_intent", "consideration"),
        "product_slugs": opts.get("product_slugs") or [],
        "network_slugs": opts.get("network_slugs") or [],
        "category": opts.get("category", "Guides"),
        "tags": opts.get("tags") or [],
        "meta_title": (opts.get("meta_title") or title)[:60],
        "meta_description": opts.get("meta_description")
        or (opts["angle"][:150] + ("…" if len(opts["angle"]) > 150 else "")),
        "outline": opts.get("outline")
        or [
            {
                "heading": "The real problem",
                "points": ["What buyers feel", "Why DIY stalls", "Cost of waiting"],
            },
            {
                "heading": "A practical framework",
                "points": ["One clear outcome", "Weekly rhythm", "Measure what matters"],
            },
            {
                "heading": "When to get help",
                "points": ["Time vs skill", "What BrandDad delivers", "Honest next step"],
            },
            {
                "heading": "FAQ",
                "points": ["Common objections", "Timeline expectations", "What not to buy yet"],
            },
        ],
    }


def default_outline(a, b, c):
    return [
        {"heading": a, "points": ["Context", "Mistake pattern", "Better default"]},
        {"heading": b, "points": ["Step one", "Step two", "Step three"]},
        {"heading": c, "points": ["DIY path", "Assisted path", "What success looks like"]},
        {"heading": "FAQ", "points": ["Budget questions", "Timeline", "Risks to avoid"]},
    ]


# --- Topic ownership map -------------------------------------------------
OWNERSHIP = {
    "version": "1.0.0",
    "sites": {
        "directory": {
            "host": "directory.branddad.social",
            "owns": [
                "local discovery",
                "business directory listings",
                "claim / join / membership",
                "WhatsApp business discovery",
                "AI Finder local search",
                "member 10% network benefit",
                "Verified / Listed / Featured badges (education)",
            ],
            "never": [
                "LinkedIn service product pitches",
                "hosting plan comparisons as primary",
                "logo package deep-dives as primary",
                "supplier / wholesaler names",
            ],
            "cta_primary": "https://directory.branddad.social/registration/",
            "cta_secondary": "https://directory.branddad.social/all-listings/",
        },
        "social": {
            "host": "branddad.social",
            "owns": [
                "marketing / visibility services",
                "LinkedIn visibility and outreach",
                "SEO packages",
                "reputation / PR / social growth",
                "GBP / local SEO management",
                "AI Ads education",
            ],
            "never": [
                "public flight booking as Directory feature",
                "LinkedIn service product",
                "hosting SKUs as primary",
                "supplier names on storefront",
            ],
            "cta_primary": "https://branddad.social/services/",
            "cta_secondary": "https://wa.me/18729105115",
        },
        "co": {
            "host": "branddad.co",
            "owns": [
                "AI logos",
                "premium websites",
                "logo+site bundles",
                "branding / apps creative",
                "design briefs",
            ],
            "never": [
                "directory membership as primary pitch",
                "hosting plan sales as primary",
                "professional outreach",
            ],
            "cta_primary": "https://branddad.co/get-started/",
            "cta_secondary": "https://branddad.co/product-category/web-design/",
        },
        "hosttech": {
            "host": "hosttech.net",
            "owns": [
                "hosting",
                "domains",
                "SSL",
                "migrations",
                "uptime / infra education",
                "AI plan guide",
            ],
            "never": [
                "logo design deep-dives as primary",
                "LinkedIn growth as primary",
                "directory Best-X-in-Y spam",
            ],
            "cta_primary": "https://hosttech.net/shopping/",
            "cta_secondary": "https://hosttech.net/domains/",
        },
    },
    "content_styles": {
        "search_traffic": "SEO/AEO helpful guides that earn discovery traffic",
        "branddad_conversion": "Education that converts into join/claim/buy — honest, not thin",
    },
    "cross_link_rules": {
        "max_cross_site_links": 2,
        "preserve_partnero_ref": True,
        "canonical_strips_ref": True,
        "noindex_ref_urls": True,
    },
    "best_x_in_y": {
        "enabled": True,
        "min_listings": 5,
        "require_real_inventory": True,
        "forbid_thin_city_category_spam": True,
        "note": "Only publish Best X in Y when Directory inventory analysis passes threshold",
    },
}


def merge_unique(existing, additions):
    by_id = {}
    for t in existing:
        if t and t.get("id"):
            by_id[t["id"]] = t
    added = 0
    for t in additions:
        if not t or not t.get("id"):
            continue
        if t["id"] in by_id:
            # keep published; refresh queued fields lightly
            if by_id[t["id"]].get("status") == "published":
                continue
            merged = dict(by_id[t["id"]])
            merged.update(t)
            by_id[t["id"]] = merged
            continue
        by_id[t["id"]] = t
        added += 1
    return list(by_id.values()), added


def load_json(path: Path):
    if not path.exists():
        return []
    return json.loads(path.read_text(encoding="utf-8"))


# --- Generate topic packs ------------------------------------------------

def directory_traffic():
    rows = []
    specs = [
        ("dir-traffic-whatsapp-discovery-2026", "whatsapp business discovery", "How WhatsApp Becomes a Local Discovery Channel", "local", ["AI Finder", "listing completeness", "member trust"]),
        ("dir-traffic-claim-vs-create-listing", "claim business directory listing", "Claim vs Create: Getting Your Business Into BrandDad Directory", "membership", ["Claim flow", "Profile fields", "Verification path"]),
        ("dir-traffic-ai-finder-prompts", "ai business finder prompts", "Better Prompts for BrandDad AI Finder Local Search", "discovery", ["Intent language", "City + category", "When results look thin"]),
        ("dir-traffic-local-categories-that-convert", "local business categories that convert", "Directory Categories That Attract Real Buyers", "listings", ["Category clarity", "Avoid junk tags", "Service radius honesty"]),
        ("dir-traffic-verified-badge-explained", "verified business badge directory", "What Verified Means on BrandDad Directory", "badges", ["Verified vs Listed", "Trust signals", "No hidden links"]),
        ("dir-traffic-listed-featured-badges", "featured listing badge", "Listed vs Featured: Badge Levels That Buyers Notice", "badges", ["When to upgrade", "Honest placement", "Member benefit stack"]),
        ("dir-traffic-near-me-without-fake-hours", "near me business search honesty", "Near-Me Discovery Without Fake Hours or Prices", "discovery", ["Inventory truth", "Contact gates", "What we refuse to invent"]),
        ("dir-traffic-member-profile-checklist", "directory member profile checklist", "A Member Profile Checklist That Gets Inquiries", "membership", ["Photos", "Offer clarity", "CTA hygiene"]),
        ("dir-traffic-cross-network-10-percent", "directory 10 percent network discount", "How the Directory 10% Works Across Social, .co, and HostTech", "membership", ["Eligible services", "Checkout recognition", "When to ask support"]),
        ("dir-traffic-local-seo-directory-role", "business directory local seo role", "Where a Business Directory Fits in Local SEO (Without Magic)", "seo", ["Citations vs spam", "GBP still matters", "Directory as trust layer"]),
        ("dir-traffic-restaurants-bars-hotels", "find restaurants bars hotels directory", "Finding Restaurants, Bars & Hotels Through Directory Search", "discovery", ["Category intent", "City filters", "WhatsApp contact"]),
        ("dir-traffic-service-businesses-join", "service business directory listing", "Why Service Businesses Join BrandDad Directory First", "membership", ["Lead quality", "Credibility", "Network savings"]),
        ("dir-traffic-tourism-corridor-discovery", "cancun tulum business discovery", "Tourism Corridor Discovery: Cancún to Tulum Without Thin Pages", "discovery", ["Inventory-backed pages", "Avoid city×category spam", "Real listings only"]),
        ("dir-traffic-guest-vs-member-browse", "directory guest browse vs member", "Guest Browse vs Member Access on BrandDad Directory", "membership", ["What guests see", "What members unlock", "Fair contact gates"]),
        ("dir-traffic-listing-photos-that-work", "business listing photo tips", "Listing Photos That Build Trust (Not Stock Clutter)", "listings", ["Hero photo", "Proof shots", "Avoid fake polish"]),
        ("dir-traffic-whatsapp-cta-hygiene", "whatsapp cta for local business", "WhatsApp CTAs That Feel Human on Directory Listings", "listings", ["Prefilled context", "Response time", "No spam scripts"]),
        ("dir-traffic-multi-location-listings", "multi location directory listing", "Multi-Location Brands: One Profile or Many Listings?", "listings", ["Clarity per city", "Brand consistency", "When to split"]),
        ("dir-traffic-reviews-without-fake-ratings", "directory reviews trust", "Reviews & Ratings Without Fake Stars", "trust", ["Source honesty", "GBP relationship", "Buyer skepticism"]),
        ("dir-traffic-seasonal-local-demand", "seasonal local business demand", "Seasonal Local Demand: How Directory Members Should Adapt", "operations", ["Peak weeks", "Offer updates", "Staffing signals"]),
        ("dir-traffic-b2b-local-suppliers", "b2b local supplier discovery", "B2B Local Supplier Discovery Without Marketplace Chaos", "discovery", ["Clear categories", "Direct WhatsApp", "Qualified intent"]),
        ("dir-traffic-directory-vs-social-ads", "directory vs social ads local", "Directory Discovery vs Social Ads for Local Intent", "strategy", ["When ads win", "When listings win", "Combined stack"]),
        ("dir-traffic-complete-your-listing", "complete business directory listing", "Finish Your Listing: The Fields Buyers Actually Use", "listings", ["Must-have fields", "Nice-to-have", "Update cadence"]),
        ("dir-traffic-member-savings-math", "directory membership savings math", "Does Directory Membership Pay for Itself? Honest Math", "membership", ["Project sizes", "Hosting renewals", "Marketing packages"]),
        ("dir-traffic-local-events-and-listings", "local events business listing", "Events & Pop-Ups: How Temporary Businesses Should List", "listings", ["Dates clarity", "Location honesty", "Remove when done"]),
        ("dir-traffic-language-and-local-search", "bilingual local business search", "Bilingual Local Search: Serving English + Spanish Intent", "discovery", ["Category naming", "Listing copy", "AI Finder prompts"]),
        ("dir-traffic-avoid-directory-spam", "avoid thin directory pages", "How BrandDad Avoids Thin City×Category Spam Pages", "seo", ["Inventory thresholds", "Canonical hygiene", "Quality over volume"]),
        ("dir-traffic-partner-referrals-clean", "directory partner referral tracking", "Partner Referrals That Track Without Duplicate SEO URLs", "affiliates", ["?ref= tracking", "Canonical clean URL", "Cookie attribution"]),
        ("dir-traffic-mobile-first-listings", "mobile directory listing ux", "Mobile-First Directory Listings: What Buyers Tap First", "ux", ["Above-the-fold CTA", "Map/contact", "Photo weight"]),
        ("dir-traffic-after-you-get-listed", "after joining business directory", "After You Get Listed: 7 Days of Useful Follow-Through", "membership", ["Profile polish", "First inquiries", "Network CTAs"]),
        ("dir-traffic-directory-for-agencies", "agency clients directory listings", "Agencies: Listing Client Businesses Without Looking Spammy", "agencies", ["Client consent", "Brand voice", "Claim handoff"]),
    ]
    for i, (tid, kw, title, cluster, points) in enumerate(specs):
        rows.append(
            topic(
                id=tid,
                cluster=cluster,
                content_style="search_traffic",
                primary_keyword=kw,
                secondary_keywords=[kw + " guide", "branddad directory", points[0].lower()],
                title=title,
                angle=f"{points[0]} — {points[1]} — {points[2]}. Practical Directory guidance without thin spam.",
                product_slugs=["branddad-directory"],
                network_slugs=["bdco-ai-websites", "hosttech-hosting"],
                category="Member Guides",
                tags=["directory", cluster, "local"],
                outline=default_outline(points[0], points[1], points[2]),
            )
        )
    return rows


def social_traffic():
    rows = []
    specs = [
        ("soc-traffic-linkedin-visibility-not-spam", "linkedin visibility for professionals", "LinkedIn Visibility That Attracts Clients (Without Cold Spam)", "linkedin"),
        ("soc-traffic-gbp-setup-checklist-2026", "google business profile setup checklist", "GBP Setup Checklist That Local Buyers Trust in 2026", "local-seo"),
        ("soc-traffic-review-growth-honest", "google review growth strategy", "Google Review Growth Without Fake Reviews", "reputation"),
        ("soc-traffic-local-seo-starter-stack", "local seo starter stack", "A Local SEO Starter Stack That Fits Real Budgets", "local-seo"),
        ("soc-traffic-website-seo-audit-scope", "website seo audit checklist", "What a Useful Website SEO Audit Actually Covers", "seo"),
        ("soc-traffic-speed-vs-conversion", "website speed optimization conversion", "Website Speed Fixes That Actually Affect Conversions", "web"),
        ("soc-traffic-social-profile-optimization", "social profile optimization", "Social Profile Optimization: The Quiet Conversion Layer", "social"),
        ("soc-traffic-ai-ads-when-ready", "ai ads for small business", "When AI Ads Make Sense (And When They Waste Spend)", "ai-ads"),
        ("soc-traffic-pr-vs-paid-visibility", "pr vs paid ads visibility", "PR Moments vs Paid Visibility: Choosing the Right Lever", "pr"),
        ("soc-traffic-reputation-repair-path", "online reputation repair steps", "Reputation Repair: A Calm Path After a Bad Review Week", "reputation"),
        ("soc-traffic-content-cadence-b2b", "b2b content cadence", "B2B Content Cadence That Does Not Burn Out Founders", "content"),
        ("soc-traffic-conversion-makeover-signals", "website conversion makeover", "Conversion Makeover Signals: Is Your Site Losing Buyers?", "web"),
        ("soc-traffic-monthly-website-care", "monthly website care plan", "Monthly Website Care: What “Maintained” Should Mean", "web"),
        ("soc-traffic-directory-distribution-service", "business directory distribution", "Directory Distribution: Getting Listed Where Buyers Look", "distribution"),
        ("soc-traffic-fix-my-website-scope", "website repair service scope", "Fix My Website: Scope Clarity Before You Buy Fixes", "web"),
        ("soc-traffic-seo-fixes-prioritization", "website seo fixes priority", "SEO Fixes: Prioritize Impact Over Busywork", "seo"),
        ("soc-traffic-linkedin-posts-framework", "linkedin posts framework professionals", "A LinkedIn Post Framework Professionals Can Reuse Weekly", "linkedin"),
        ("soc-traffic-visibility-without-trust", "brand visibility without trust", "Visibility Without Trust Is Just Noise", "strategy"),
        ("soc-traffic-influencer-engagements", "influencer engagements for brands", "Influencer Engagements That Help Brands (Not Just Reach)", "social"),
        ("soc-traffic-imdb-presence-explained", "professional imdb presence", "Professional IMDb Presence Explained for Creators & Brands", "pr"),
        ("soc-traffic-times-square-billboard", "times square billboard worth it", "When a Times Square Billboard Is Worth It", "pr"),
        ("soc-traffic-social-management-beats-bursts", "social media management consistency", "Consistent Social Management Beats Campaign Bursts", "social"),
        ("soc-traffic-member-10-percent-services", "directory members save on marketing", "Directory Members: How 10% Off Applies to Marketing Services", "membership"),
        ("soc-traffic-choose-service-quiz-logic", "choose marketing service", "How to Choose Your Next Marketing Service Without Guessing", "strategy"),
        ("soc-traffic-whatsapp-support-buying", "whatsapp support before buying service", "Use WhatsApp Before Buying: Questions Worth Asking", "support"),
        ("soc-traffic-local-web-foundation", "local and web foundation services", "Fix the Foundation: Local & Web Services That Unlock Growth", "local-seo"),
        ("soc-traffic-ai-ads-tiers-explained", "ai ads tiers explained", "AI Ads Tiers Explained: Setup Through Scale", "ai-ads"),
        ("soc-traffic-seo-management-starter-vs-growth", "local seo management plans", "Local SEO Management: Starter vs Growth — Honest Fit", "local-seo"),
        ("soc-traffic-review-setup-vs-monthly", "google review growth setup vs monthly", "Review Growth: Setup Project vs Monthly Momentum", "reputation"),
        ("soc-traffic-cross-site-when-to-send", "when to send clients to branddad co hosttech", "When to Send Clients to BrandDad.co or HostTech", "ecosystem"),
    ]
    for tid, kw, title, cluster in specs:
        rows.append(
            topic(
                id=tid,
                cluster=cluster,
                content_style="search_traffic",
                primary_keyword=kw,
                secondary_keywords=[kw + " 2026", "branddad social", cluster + " marketing"],
                title=title,
                angle=f"Practical {cluster} guidance for brands that need visibility and revenue — soft CTA to BrandDad Social services, never professional outreach.",
                product_slugs=["website-seo-audit", "local-seo-management-starter", "gbp-setup-optimization"],
                network_slugs=["branddad-directory", "bdco-ai-websites", "hosttech-hosting"],
                category="Growth Guides",
                tags=["marketing", cluster, "branddad-social"],
            )
        )
    return rows


def co_traffic():
    rows = []
    specs = [
        ("co-traffic-brief-ai-logo", "how to brief an ai logo designer", "How to Brief an AI Logo Project That Actually Works"),
        ("co-traffic-premium-ai-website-vs-diy", "premium ai website vs diy builders", "Premium AI Website vs DIY Builders: Honest Tradeoffs"),
        ("co-traffic-logo-website-bundle", "logo website bundle when it wins", "Logo + Website Bundle: When the Bundle Wins"),
        ("co-traffic-website-handshake", "website after discovery handshake", "Your Website Is the Handshake After Discovery"),
        ("co-traffic-logo-pack-usage", "ai logo pack usage rights", "AI Logo Packs: Usage, Variations, and File Hygiene"),
        ("co-traffic-brand-colors-from-logo", "brand colors from logo", "Pulling Brand Colors From a New Logo Without Chaos"),
        ("co-traffic-homepage-first-viewport", "homepage first viewport design", "Homepage First Viewport: Brand First, Not Dashboard Clutter"),
        ("co-traffic-service-page-structure", "service page website structure", "Service Page Structure That Helps Buyers Decide"),
        ("co-traffic-portfolio-vs-proof", "website portfolio vs proof", "Portfolio vs Proof: What Premium Sites Should Show"),
        ("co-traffic-mobile-nav-for-local", "mobile navigation local business site", "Mobile Nav for Local Business Sites That Convert"),
        ("co-traffic-cta-hierarchy", "website cta hierarchy", "CTA Hierarchy: One Primary Action Per Section"),
        ("co-traffic-typography-brand-feel", "website typography branding", "Typography Choices That Signal Premium Without Gimmicks"),
        ("co-traffic-photo-direction", "website photo direction brief", "Photo Direction Briefs for BrandDad Website Builds"),
        ("co-traffic-app-icon-vs-logo", "app icon vs logo design", "App Icon vs Logo: When You Need Both"),
        ("co-traffic-rebrand-timing", "when to rebrand logo website", "When a Rebrand Is Worth It (And When It Is Not)"),
        ("co-traffic-hosting-after-design", "hosting after website design", "After Design: Handing Off to HostTech Cleanly"),
        ("co-traffic-seo-basics-on-new-site", "seo basics new website", "SEO Basics Built Into a New BrandDad Website"),
        ("co-traffic-forms-that-convert", "website contact form conversion", "Contact Forms That Convert Without Feeling Pushy"),
        ("co-traffic-multi-language-sites", "multilingual website design", "Multilingual Sites: Structure Before Translation Sprawl"),
        ("co-traffic-ecommerce-lite", "lite ecommerce website design", "Lite Ecommerce: When a Simple Shop Beats a Platform"),
        ("co-traffic-booking-pages", "appointment booking website page", "Booking Pages That Reduce No-Shows"),
        ("co-traffic-brand-guidelines-lite", "lite brand guidelines", "Lite Brand Guidelines Your Team Will Actually Use"),
        ("co-traffic-revision-rounds", "logo website revision rounds", "Revision Rounds: How to Give Feedback That Speeds Delivery"),
        ("co-traffic- competito r-differentiation", "differentiate website from competitors", "Differentiate Without Copying Competitors’ Sites"),
        ("co-traffic-launch-checklist", "website launch checklist", "Website Launch Checklist Before You Announce"),
        ("co-traffic-accessibility-basics", "website accessibility basics", "Accessibility Basics That Premium Sites Should Not Skip"),
        ("co-traffic-performance-budgets", "website performance budget", "Performance Budgets for Beautiful Sites"),
        ("co-traffic-directory-member-design", "directory member website discount", "Directory Members: Design Work With Network Savings"),
        ("co-traffic-growth-after-launch", "marketing after website launch", "After Launch: When to Bring in BrandDad Social Growth"),
        ("co-traffic-get-started-path", "branddad co get started", "The Cleanest Path to Start on BrandDad.co"),
    ]
    # fix accidental space in one id
    fixed = []
    for tid, kw, title in specs:
        tid = tid.replace(" ", "")
        fixed.append((tid, kw, title))
    for tid, kw, title in fixed:
        rows.append(
            topic(
                id=tid,
                cluster="design",
                content_style="search_traffic",
                primary_keyword=kw,
                secondary_keywords=[kw + " guide", "branddad.co", "ai logo website"],
                title=title,
                angle="Premium design-studio guidance for logos and websites — calm confidence, soft handoff to HostTech hosting and BrandDad Social growth when needed.",
                product_slugs=["bdco-ai-logos", "bdco-ai-websites"],
                network_slugs=["hosttech-hosting", "branddad-directory"],
                category="Design Studio",
                tags=["design", "branddad-co", "websites"],
            )
        )
    return rows


def hosttech_traffic():
    rows = []
    specs = [
        ("ht-traffic-what-is-cloud-hosting", "what is cloud hosting", "What Is Cloud Hosting, How It Works, and Who It Fits"),
        ("ht-traffic-hostgator-backed-explained", "hostgator backed hosting", "What HostGator-Backed Hosting Means for Your Site"),
        ("ht-traffic-wordpress-migration-basics", "wordpress migration basics", "WordPress Migration Basics That Prevent Broken Cutover Day"),
        ("ht-traffic-domain-registration-checklist", "domain registration checklist", "Domain Registration Checklist Before You Hit Buy"),
        ("ht-traffic-ssl-explained", "ssl certificate explained", "SSL Certificates Explained Without Fear Marketing"),
        ("ht-traffic-dns-basics-business", "dns basics for business owners", "DNS Basics Business Owners Actually Need"),
        ("ht-traffic-choose-hosting-plan", "choose web hosting plan", "How to Choose a Hosting Plan Without Overbuying"),
        ("ht-traffic-ai-plan-guide", "ai hosting plan guide", "Using HostTech’s AI Plan Guide Without Guesswork"),
        ("ht-traffic-uptime-monitoring", "website uptime monitoring", "Uptime Monitoring: What to Watch and What to Ignore"),
        ("ht-traffic-email-on-domain", "business email on domain", "Business Email on Your Domain: Clean Setup Path"),
        ("ht-traffic-staging-before-launch", "wordpress staging environment", "Staging Before Launch: Fewer Public Mistakes"),
        ("ht-traffic-backups-restore-drill", "website backup restore drill", "Backups Are Useless Until You Practice a Restore"),
        ("ht-traffic-php-updates-wordpress", "php updates wordpress hosting", "PHP Updates on WordPress Hosting: Timing and Risk"),
        ("ht-traffic-cdn-when-needed", "cdn for wordpress site", "When a CDN Helps (And When It Adds Complexity)"),
        ("ht-traffic-security-basics", "wordpress hosting security basics", "Hosting Security Basics Without Scare Tactics"),
        ("ht-traffic-malware-recovery-lessons", "wordpress malware recovery", "Malware Recovery Lessons From Real Compromise Cases"),
        ("ht-traffic-multisite-or-not", "wordpress multisite hosting", "WordPress Multisite: When It Helps Network Brands"),
        ("ht-traffic-transfer-domain", "transfer domain to hosttech", "Transferring a Domain to HostTech Without Downtime Drama"),
        ("ht-traffic-nameservers-change", "change nameservers checklist", "Nameserver Changes: A Calm Checklist"),
        ("ht-traffic-performance-hosting-layer", "hosting performance optimization", "Performance at the Hosting Layer vs Theme Bloat"),
        ("ht-traffic-managed-vs-diy", "managed hosting vs diy", "Managed Hosting vs DIY: Honest Fit for Small Teams"),
        ("ht-traffic-renewals-calendar", "domain hosting renewal calendar", "Renewals Calendar: Domains, Hosting, SSL"),
        ("ht-traffic-https-mixed-content", "fix mixed content https", "Fixing Mixed Content After HTTPS"),
        ("ht-traffic-database-growth", "wordpress database growth", "WordPress Database Growth: What to Clean Safely"),
        ("ht-traffic-cron-and-jobs", "wordpress cron hosting", "WP-Cron and Real Cron: Reliability on Shared Hosts"),
        ("ht-traffic-client-handoff", "client hosting handoff", "Client Hosting Handoff Checklist for Agencies"),
        ("ht-traffic-branddad-co-after-hosting", "hosting then website design", "Hosting First or Design First? Sequencing BrandDad.co"),
        ("ht-traffic-directory-member-hosting", "directory member hosting discount", "Directory Members: Hosting With Network Savings"),
        ("ht-traffic-seo-hosting-myths", "hosting seo myths", "Hosting SEO Myths That Waste Budget"),
        ("ht-traffic-shopping-plans-explained", "hosttech shopping plans", "HostTech Shopping Plans Explained in Plain English"),
    ]
    for tid, kw, title in specs:
        rows.append(
            topic(
                id=tid,
                cluster="hosting",
                content_style="search_traffic",
                primary_keyword=kw,
                secondary_keywords=[kw + " guide", "hosttech", "wordpress hosting"],
                title=title,
                angle="Clear hosting educator voice — practical checklists, honest tradeoffs, soft upsell BrandDad.co websites and BrandDad Social when demand is the bottleneck.",
                product_slugs=["hosttech-hosting", "hosttech-domains"],
                network_slugs=["bdco-ai-websites", "branddad-directory"],
                category="Hosting Guides",
                tags=["hosting", "hosttech", "infra"],
            )
        )
    return rows


def conversion_topics():
    """30 conversion/education — split Directory (join/claim) + Social (ecosystem)."""
    dir_conv = [
        ("conv-dir-join-before-purchase", "join directory before purchase", "Join the Directory Before Your Next BrandDad Purchase", "membership"),
        ("conv-dir-claim-your-business", "claim your business directory", "Claim Your Business on BrandDad Directory Today", "claim"),
        ("conv-dir-why-members-convert", "why join branddad directory", "Why Serious Businesses Join BrandDad Directory", "membership"),
        ("conv-dir-complete-claim-in-15", "complete directory claim", "Complete Your Directory Claim in One Focused Session", "claim"),
        ("conv-dir-member-stack-savings", "directory member stack savings", "The Member Stack: Directory + Services + Design + Hosting", "membership"),
        ("conv-dir-listing-to-first-inquiry", "first inquiry from directory listing", "From Listing Live to First Inquiry: A 14-Day Plan", "conversion"),
        ("conv-dir-verified-path", "get verified on branddad directory", "The Path to Verified on BrandDad Directory", "badges"),
        ("conv-dir-featured-when", "when to get featured listing", "When Featured Makes Sense (And When Listed Is Enough)", "badges"),
        ("conv-dir-whatsapp-ready-profile", "whatsapp ready business profile", "Make Your Profile WhatsApp-Ready Before You Promote It", "listings"),
        ("conv-dir-invite-partners", "invite partners to directory", "Invite Partners to the Directory Without Spamming Them", "growth"),
        ("conv-dir-agency-onboarding", "agency onboard clients directory", "Agency Onboarding: Get Clients Listed Cleanly", "agencies"),
        ("conv-dir-renew-membership-value", "renew directory membership", "Renew With Intent: Membership Value Beyond Year One", "membership"),
        ("conv-dir-local-offer-clarity", "clarify local business offer", "Clarify Your Offer So Directory Visitors Can Say Yes", "conversion"),
        ("conv-dir-photo-proof-pack", "listing proof photos", "A Proof Photo Pack That Raises Inquiry Quality", "listings"),
        ("conv-dir-after-claim-checklist", "after claiming directory listing", "After You Claim: The Only Checklist That Matters", "claim"),
    ]
    soc_conv = [
        ("conv-soc-ecosystem-map", "branddad ecosystem map", "BrandDad Ecosystem Map: Social, Directory, .co, HostTech", "ecosystem"),
        ("conv-soc-pick-next-service", "pick next branddad service", "Pick Your Next BrandDad Service Without Overwhelm", "conversion"),
        ("conv-soc-directory-then-marketing", "directory then marketing services", "Directory First, Marketing Second: A Sensible Order", "strategy"),
        ("conv-soc-design-then-host", "website design then hosting", "Design Then Host: Sequencing .co and HostTech", "ecosystem"),
        ("conv-soc-member-discount-howto", "how to use directory 10 percent", "How to Use Your Directory 10% on BrandDad Social", "membership"),
        ("conv-soc-whatsapp-recommend", "whatsapp recommend branddad service", "Ask on WhatsApp: How We Recommend the Right Service", "support"),
        ("conv-soc-audit-to-fixes", "seo audit to fixes path", "From SEO Audit to Fixes: The Honest Buying Path", "seo"),
        ("conv-soc-gbp-to-reviews", "gbp setup to review growth", "From GBP Setup to Review Growth: Stack That Compounds", "local-seo"),
        ("conv-soc-foundation-before-ads", "foundation before ai ads", "Foundation Before AI Ads: Don’t Scale a Leaky Site", "ai-ads"),
        ("conv-soc-visibility-to-revenue", "visibility to revenue system", "Visibility to Revenue: The BrandDad Operating Idea", "strategy"),
        ("conv-soc-linkedin-services-scope", "linkedin services", "Why BrandDad Does Not Sell LinkedIn Cold Outreach", "trust"),
        ("conv-soc-affiliate-path", "branddad affiliate path", "Affiliate Path: Earn Recurring Without Gaming SEO", "affiliates"),
        ("conv-soc-bundle-vs-a-la-carte", "marketing service bundle vs a la carte", "Bundle vs À La Carte: Buying Marketing Help Cleanly", "conversion"),
        ("conv-soc-quarterly-growth-rhythm", "quarterly growth rhythm", "A Quarterly Growth Rhythm Across the BrandDad Network", "strategy"),
        ("conv-soc-start-here-new-brand", "start here new brand branddad", "New Brand Start-Here: Directory, Site, Host, Then Demand", "ecosystem"),
    ]
    rows = []
    for tid, kw, title, cluster in dir_conv:
        rows.append(
            topic(
                id=tid,
                cluster=cluster,
                content_style="branddad_conversion",
                primary_keyword=kw,
                secondary_keywords=[kw + " branddad", "join directory", "member benefits"],
                title=title,
                angle="Conversion education for Directory join/claim — honest CTAs, no fake scarcity, no supplier leaks.",
                product_slugs=["branddad-directory"],
                network_slugs=["linkedin-viral-posts-for-professionals", "bdco-ai-websites", "hosttech-hosting"],
                category="Member Guides",
                tags=["conversion", "directory", cluster],
                buyer_intent="decision",
            )
        )
    for tid, kw, title, cluster in soc_conv:
        rows.append(
            topic(
                id=tid,
                cluster=cluster,
                content_style="branddad_conversion",
                primary_keyword=kw,
                secondary_keywords=[kw + " branddad", "branddad services", "ecosystem"],
                title=title,
                angle="Conversion/education across the BrandDad ecosystem — clear next steps to services, Directory, .co, or HostTech without cannibalizing topic ownership.",
                product_slugs=["website-seo-audit", "gbp-setup-optimization", "business-directory-distribution"],
                network_slugs=["branddad-directory", "bdco-ai-websites", "hosttech-hosting"],
                category="Growth Guides",
                tags=["conversion", "ecosystem", cluster],
                buyer_intent="decision",
            )
        )
    return rows


def write_topics():
    summary = {}
    # Directory: merge traffic + half conversion into directory file
    dir_existing = load_json(SITES / "directory-topics.json")
    dir_new = directory_traffic() + [t for t in conversion_topics() if t["id"].startswith("conv-dir-")]
    dir_all, added = merge_unique(dir_existing, dir_new)
    (SITES / "directory-topics.json").write_text(json.dumps(dir_all, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    summary["directory"] = {"total": len(dir_all), "added": added, "queued": sum(1 for t in dir_all if t.get("status") == "queued")}

    # Social: traffic + social conversion into topics-seed
    soc_existing = load_json(AB_DATA / "topics-seed.json")
    soc_new = social_traffic() + [t for t in conversion_topics() if t["id"].startswith("conv-soc-")]
    soc_all, added = merge_unique(soc_existing, soc_new)
    (AB_DATA / "topics-seed.json").write_text(json.dumps(soc_all, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    summary["social"] = {"total": len(soc_all), "added": added, "queued": sum(1 for t in soc_all if t.get("status") == "queued")}

    co_existing = load_json(SITES / "co-topics.json")
    co_all, added = merge_unique(co_existing, co_traffic())
    (SITES / "co-topics.json").write_text(json.dumps(co_all, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    summary["co"] = {"total": len(co_all), "added": added, "queued": sum(1 for t in co_all if t.get("status") == "queued")}

    ht_existing = load_json(SITES / "hosttech-topics.json")
    ht_all, added = merge_unique(ht_existing, hosttech_traffic())
    (SITES / "hosttech-topics.json").write_text(json.dumps(ht_all, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    summary["hosttech"] = {"total": len(ht_all), "added": added, "queued": sum(1 for t in ht_all if t.get("status") == "queued")}

    # Standalone conversion pack for report / ecosystem MU
    conv = conversion_topics()
    (DATA / "conversion-topics.json").write_text(json.dumps(conv, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    summary["conversion_pack"] = {"total": len(conv)}

    (DATA / "topic-ownership.json").write_text(json.dumps(OWNERSHIP, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    # also copy into autoblog data for plugin access
    (AB_DATA / "topic-ownership.json").write_text(json.dumps(OWNERSHIP, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    (ROOT / "_seo_ecosystem_topics_summary.json").write_text(json.dumps(summary, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(summary, indent=2))


MU_PHP = r'''<?php
/**
 * Plugin Name: BrandDad SEO Ecosystem
 * Description: Unified SEO + content + conversion engine across BrandDad network — Partnero canonical/noindex, topic ownership, contextual modules, opportunity scoring hooks, admin MVP, 1/day network cadence coordinator.
 * Version: 1.0.0
 * Author: BrandDad
 *
 * Deploy: wp-content/mu-plugins/bd-seo-ecosystem-mu.php
 * Optional data: wp-content/mu-plugins/branddad-seo-ecosystem/data/*.json
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BD_SEO_ECO_VER' ) ) {
	return;
}
define( 'BD_SEO_ECO_VER', '1.0.0' );
define( 'BD_SEO_ECO_OPT', 'bd_seo_eco_settings' );
define( 'BD_SEO_ECO_OWN', 'bd_seo_eco_ownership' );
define( 'BD_SEO_ECO_SCORE', 'bd_seo_eco_opportunity_scores' );
define( 'BD_SEO_ECO_NET_CRON', 'bd_seo_eco_network_daily' );

/**
 * Detect site id.
 *
 * @return string
 */
function bd_seo_eco_site_id() {
	$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$host = strtolower( (string) $host );
	$host = preg_replace( '/^www\./', '', $host );
	if ( false !== strpos( $host, 'directory.branddad' ) ) {
		return 'directory';
	}
	if ( false !== strpos( $host, 'branddad.social' ) ) {
		return 'social';
	}
	if ( false !== strpos( $host, 'branddad.co' ) ) {
		return 'co';
	}
	if ( false !== strpos( $host, 'hosttech' ) ) {
		return 'hosttech';
	}
	return 'unknown';
}

/**
 * Default settings.
 *
 * @return array
 */
function bd_seo_eco_defaults() {
	return array(
		'enabled'              => 1,
		'partnero_noindex_ref' => 1,
		'fix_blog_canonical'  => 1,
		'contextual_modules'   => 1,
		'network_daily'        => 1,
		'network_daily_hour'   => 10,
		'network_site_order'   => array( 'directory', 'social', 'co', 'hosttech' ),
		'autoblog_publish'     => 1,
		'status_publish'       => 1,
	);
}

/**
 * @return array
 */
function bd_seo_eco_settings() {
	$s = get_option( BD_SEO_ECO_OPT, array() );
	if ( ! is_array( $s ) ) {
		$s = array();
	}
	return array_merge( bd_seo_eco_defaults(), $s );
}

/**
 * Load ownership map from option or bundled JSON.
 *
 * @return array
 */
function bd_seo_eco_ownership() {
	$opt = get_option( BD_SEO_ECO_OWN, null );
	if ( is_array( $opt ) && ! empty( $opt['sites'] ) ) {
		return $opt;
	}
	$paths = array(
		WP_CONTENT_DIR . '/mu-plugins/branddad-seo-ecosystem/data/topic-ownership.json',
		WP_PLUGIN_DIR . '/branddad-autoblog/data/topic-ownership.json',
		dirname( __FILE__ ) . '/branddad-seo-ecosystem/data/topic-ownership.json',
	);
	foreach ( $paths as $p ) {
		if ( is_string( $p ) && file_exists( $p ) ) {
			$data = json_decode( (string) file_get_contents( $p ), true );
			if ( is_array( $data ) && ! empty( $data['sites'] ) ) {
				update_option( BD_SEO_ECO_OWN, $data, false );
				return $data;
			}
		}
	}
	return array( 'sites' => array(), 'version' => BD_SEO_ECO_VER );
}

/**
 * Whether request carries Partnero ref query.
 *
 * @return bool
 */
function bd_seo_eco_has_ref_query() {
	return isset( $_GET['ref'] ) || isset( $_GET['partnero_partner'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Clean permalink for current request (no ref).
 *
 * @return string
 */
function bd_seo_eco_clean_url() {
	$url = '';
	if ( is_singular() ) {
		$url = get_permalink( get_queried_object_id() );
	} elseif ( is_home() && ! is_front_page() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$url            = $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/blog/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : '';
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
	} elseif ( is_front_page() ) {
		$url = home_url( '/' );
	}
	if ( ! is_string( $url ) || is_wp_error( $url ) || $url === '' ) {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$url  = home_url( $path ? $path : '/' );
	}
	// Strip tracking query args from canonical.
	$url = remove_query_arg( array( 'ref', 'partnero_partner', 'fbclid', 'gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'nocache', 'cv', 'v', 'chipcheck' ), $url );
	return $url;
}

/** Partnero: track ?ref= but do not index duplicates. */
function bd_seo_eco_robots_noindex_ref( $robots ) {
	$s = bd_seo_eco_settings();
	if ( empty( $s['partnero_noindex_ref'] ) || ! bd_seo_eco_has_ref_query() ) {
		return $robots;
	}
	if ( is_array( $robots ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'bd_seo_eco_robots_noindex_ref', 45 );

function bd_seo_eco_header_noindex_ref() {
	$s = bd_seo_eco_settings();
	if ( empty( $s['partnero_noindex_ref'] ) || ! bd_seo_eco_has_ref_query() ) {
		return;
	}
	if ( ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, follow', true );
	}
}
add_action( 'send_headers', 'bd_seo_eco_header_noindex_ref', 5 );
add_action( 'template_redirect', 'bd_seo_eco_header_noindex_ref', 0 );

function bd_seo_eco_filter_canonical( $url ) {
	$s = bd_seo_eco_settings();
	if ( empty( $s['partnero_noindex_ref'] ) && empty( $s['fix_blog_canonical'] ) ) {
		return $url;
	}
	$clean = bd_seo_eco_clean_url();
	return $clean ? $clean : $url;
}
add_filter( 'wpseo_canonical', 'bd_seo_eco_filter_canonical', 40 );
add_filter( 'rank_math/frontend/canonical', 'bd_seo_eco_filter_canonical', 40 );
add_filter( 'get_canonical_url', 'bd_seo_eco_filter_canonical', 40 );

function bd_seo_eco_print_canonical_fallback() {
	$s = bd_seo_eco_settings();
	if ( empty( $s['enabled'] ) ) {
		return;
	}
	// If a major SEO plugin already printed canonical, still ensure clean URL when ?ref= present.
	$clean = bd_seo_eco_clean_url();
	if ( ! $clean ) {
		return;
	}
	echo '<link rel="canonical" href="' . esc_url( $clean ) . '" data-bd-seo-eco="1" />' . "\n";
	if ( bd_seo_eco_has_ref_query() && ! empty( $s['partnero_noindex_ref'] ) ) {
		echo '<meta name="robots" content="noindex, follow" data-bd-seo-eco="1" />' . "\n";
	}
}
add_action( 'wp_head', 'bd_seo_eco_print_canonical_fallback', 2 );

/**
 * Contextual related module HTML (same-site + cross-site by ownership).
 *
 * @param array $args Args.
 * @return string
 */
function bd_seo_eco_related_module( $args = array() ) {
	$s = bd_seo_eco_settings();
	if ( empty( $s['contextual_modules'] ) ) {
		return '';
	}
	$site = bd_seo_eco_site_id();
	$map  = bd_seo_eco_ownership();
	$row  = isset( $map['sites'][ $site ] ) ? $map['sites'][ $site ] : array();
	$pri  = isset( $row['cta_primary'] ) ? $row['cta_primary'] : home_url( '/' );
	$sec  = isset( $row['cta_secondary'] ) ? $row['cta_secondary'] : '';

	// Preserve Partnero ref on outbound BrandDad links when helper exists.
	if ( function_exists( 'bds_po_url' ) ) {
		$pri = bds_po_url( $pri );
		if ( $sec ) {
			$sec = bds_po_url( $sec );
		}
	}

	$owns = isset( $row['owns'] ) && is_array( $row['owns'] ) ? $row['owns'] : array();
	$label = 'Next step on this BrandDad site';
	$html  = '<aside class="bd-seo-eco-related" data-bd-seo-eco="related">'
		. '<p class="bd-seo-eco-related__eyebrow">' . esc_html( $label ) . '</p>'
		. '<p><a href="' . esc_url( $pri ) . '">' . esc_html( wp_parse_url( $pri, PHP_URL_HOST ) ?: 'Continue' ) . '</a>';
	if ( $sec ) {
		$html .= ' · <a href="' . esc_url( $sec ) . '">Related</a>';
	}
	$html .= '</p>';
	if ( $owns ) {
		$html .= '<p class="bd-seo-eco-related__owns">This site owns: ' . esc_html( implode( ', ', array_slice( $owns, 0, 4 ) ) ) . '.</p>';
	}
	$html .= '</aside>';
	return $html;
}

function bd_seo_eco_append_related( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'bd-seo-eco-related' ) ) {
		return $content;
	}
	return $content . "\n\n" . bd_seo_eco_related_module();
}
add_filter( 'the_content', 'bd_seo_eco_append_related', 35 );

/**
 * Lightweight opportunity scoring hook (architecture).
 * Scores published posts missing thumbnails / thin content / stale.
 */
function bd_seo_eco_score_opportunities( $limit = 40 ) {
	$q = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, min( 100, (int) $limit ) ),
			'orderby'                => 'modified',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);
	$out = array();
	foreach ( $q->posts as $pid ) {
		$pid   = (int) $pid;
		$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $pid ) ) );
		$thumb = (int) get_post_thumbnail_id( $pid );
		$age   = time() - (int) get_post_modified_time( 'U', true, $pid );
		$score = 0;
		$reasons = array();
		if ( $thumb < 1 ) {
			$score += 40;
			$reasons[] = 'missing_featured_image';
		}
		if ( $words < 600 ) {
			$score += 35;
			$reasons[] = 'thin_content';
		}
		if ( $age > YEAR_IN_SECONDS ) {
			$score += 15;
			$reasons[] = 'stale';
		}
		if ( $score < 1 ) {
			continue;
		}
		$out[] = array(
			'post_id' => $pid,
			'title'   => get_the_title( $pid ),
			'url'     => get_permalink( $pid ),
			'score'   => $score,
			'reasons' => $reasons,
			'words'   => $words,
		);
	}
	usort(
		$out,
		function ( $a, $b ) {
			return (int) $b['score'] - (int) $a['score'];
		}
	);
	update_option( BD_SEO_ECO_SCORE, array( 'at' => gmdate( 'c' ), 'site' => bd_seo_eco_site_id(), 'items' => array_slice( $out, 0, 50 ) ), false );
	return $out;
}

/**
 * Network daily: on designated site day, force one Autoblog publish if available.
 * Rotation stored in option so ecosystem posts 1/day across sites without flooding each.
 */
function bd_seo_eco_network_daily_run() {
	$s = bd_seo_eco_settings();
	if ( empty( $s['enabled'] ) || empty( $s['network_daily'] ) ) {
		return;
	}
	$hour = isset( $s['network_daily_hour'] ) ? (int) $s['network_daily_hour'] : 10;
	if ( (int) wp_date( 'G' ) !== $hour ) {
		return;
	}
	$order = isset( $s['network_site_order'] ) && is_array( $s['network_site_order'] ) ? $s['network_site_order'] : array( 'directory', 'social', 'co', 'hosttech' );
	$day   = (int) floor( time() / DAY_IN_SECONDS );
	$idx   = $day % max( 1, count( $order ) );
	$turn  = $order[ $idx ];
	$here  = bd_seo_eco_site_id();
	if ( $here !== $turn ) {
		update_option(
			'bd_seo_eco_last_daily',
			array(
				'at'   => gmdate( 'c' ),
				'here' => $here,
				'turn' => $turn,
				'skip' => 'not_this_sites_turn',
			),
			false
		);
		return;
	}
	if ( empty( $s['autoblog_publish'] ) || ! class_exists( 'BDS_AB_Publisher', false ) ) {
		update_option(
			'bd_seo_eco_last_daily',
			array(
				'at'   => gmdate( 'c' ),
				'here' => $here,
				'turn' => $turn,
				'skip' => 'autoblog_unavailable',
			),
			false
		);
		return;
	}
	// Ensure publish status for ongoing daily.
	if ( ! empty( $s['status_publish'] ) && class_exists( 'BrandDad_Autoblog', false ) ) {
		$ab = BrandDad_Autoblog::settings();
		$ab['status']  = 'publish';
		$ab['enabled'] = 1;
		$ab['cadence'] = '1_per_day';
		update_option( 'bds_ab_settings', $ab, false );
	}
	$result = BDS_AB_Publisher::publish_next( true );
	$payload = array(
		'at'   => gmdate( 'c' ),
		'here' => $here,
		'turn' => $turn,
	);
	if ( is_wp_error( $result ) ) {
		$payload['error'] = $result->get_error_message();
	} elseif ( null === $result ) {
		$payload['skip'] = 'nothing_to_publish';
	} else {
		$payload['post_id'] = (int) $result;
		$payload['url']     = get_permalink( (int) $result );
	}
	update_option( 'bd_seo_eco_last_daily', $payload, false );
}

function bd_seo_eco_cron_init() {
	add_action( BD_SEO_ECO_NET_CRON, 'bd_seo_eco_network_daily_run' );
	add_filter(
		'cron_schedules',
		function ( $schedules ) {
			$schedules['bd_seo_eco_hourly'] = array(
				'interval' => HOUR_IN_SECONDS,
				'display'  => 'BrandDad SEO Ecosystem Hourly',
			);
			return $schedules;
		}
	);
	if ( ! wp_next_scheduled( BD_SEO_ECO_NET_CRON ) ) {
		wp_schedule_event( time() + 120, 'bd_seo_eco_hourly', BD_SEO_ECO_NET_CRON );
	}
}
add_action( 'init', 'bd_seo_eco_cron_init', 40 );

/** Admin MVP dashboard */
function bd_seo_eco_admin_menu() {
	add_options_page(
		'BrandDad SEO Ecosystem',
		'SEO Ecosystem',
		'manage_options',
		'bd-seo-ecosystem',
		'bd_seo_eco_admin_render'
	);
}
add_action( 'admin_menu', 'bd_seo_eco_admin_menu' );

function bd_seo_eco_admin_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['bd_seo_eco_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bd_seo_eco_nonce'] ) ), 'bd_seo_eco_save' ) ) {
		$raw = isset( $_POST['bd_seo_eco'] ) && is_array( $_POST['bd_seo_eco'] ) ? wp_unslash( $_POST['bd_seo_eco'] ) : array();
		$s   = bd_seo_eco_settings();
		foreach ( array( 'enabled', 'partnero_noindex_ref', 'fix_blog_canonical', 'contextual_modules', 'network_daily', 'autoblog_publish', 'status_publish' ) as $k ) {
			$s[ $k ] = empty( $raw[ $k ] ) ? 0 : 1;
		}
		if ( isset( $raw['network_daily_hour'] ) ) {
			$s['network_daily_hour'] = max( 0, min( 23, absint( $raw['network_daily_hour'] ) ) );
		}
		update_option( BD_SEO_ECO_OPT, $s, false );
		echo '<div class="updated"><p>Saved.</p></div>';
	}
	if ( isset( $_POST['bd_seo_eco_score'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bd_seo_eco_nonce'] ) ), 'bd_seo_eco_save' ) ) {
		bd_seo_eco_score_opportunities( 40 );
		echo '<div class="updated"><p>Opportunity scores refreshed.</p></div>';
	}
	$s    = bd_seo_eco_settings();
	$own  = bd_seo_eco_ownership();
	$last = get_option( 'bd_seo_eco_last_daily', array() );
	$score = get_option( BD_SEO_ECO_SCORE, array() );
	$site = bd_seo_eco_site_id();
	?>
	<div class="wrap">
		<h1>BrandDad SEO Ecosystem <code><?php echo esc_html( BD_SEO_ECO_VER ); ?></code></h1>
		<p>Site: <strong><?php echo esc_html( $site ); ?></strong> · Coordinates Autoblog + Partnero SEO + topic ownership. Does not weaken CSRF/auth.</p>
		<form method="post">
			<?php wp_nonce_field( 'bd_seo_eco_save', 'bd_seo_eco_nonce' ); ?>
			<table class="form-table">
				<tr><th>Modules</th><td>
					<label><input type="checkbox" name="bd_seo_eco[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> Enabled</label><br>
					<label><input type="checkbox" name="bd_seo_eco[partnero_noindex_ref]" value="1" <?php checked( ! empty( $s['partnero_noindex_ref'] ) ); ?> /> Partnero ?ref= → noindex + canonical clean URL</label><br>
					<label><input type="checkbox" name="bd_seo_eco[fix_blog_canonical]" value="1" <?php checked( ! empty( $s['fix_blog_canonical'] ) ); ?> /> Fix blog/archive canonicals</label><br>
					<label><input type="checkbox" name="bd_seo_eco[contextual_modules]" value="1" <?php checked( ! empty( $s['contextual_modules'] ) ); ?> /> Contextual related modules on posts</label><br>
					<label><input type="checkbox" name="bd_seo_eco[network_daily]" value="1" <?php checked( ! empty( $s['network_daily'] ) ); ?> /> Network 1/day rotation (this site publishes only on its turn)</label><br>
					<label><input type="checkbox" name="bd_seo_eco[autoblog_publish]" value="1" <?php checked( ! empty( $s['autoblog_publish'] ) ); ?> /> Use Autoblog publisher for daily</label><br>
					<label><input type="checkbox" name="bd_seo_eco[status_publish]" value="1" <?php checked( ! empty( $s['status_publish'] ) ); ?> /> Force Autoblog status=publish for daily</label>
				</td></tr>
				<tr><th>Daily hour (site TZ)</th><td>
					<input type="number" min="0" max="23" name="bd_seo_eco[network_daily_hour]" value="<?php echo esc_attr( (string) $s['network_daily_hour'] ); ?>" />
				</td></tr>
			</table>
			<?php submit_button( 'Save settings' ); ?>
			<button class="button" name="bd_seo_eco_score" value="1">Refresh opportunity scores</button>
		</form>
		<hr>
		<h2>Topic ownership (<?php echo esc_html( isset( $own['version'] ) ? $own['version'] : '' ); ?>)</h2>
		<pre style="max-height:280px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px;"><?php echo esc_html( wp_json_encode( isset( $own['sites'][ $site ] ) ? $own['sites'][ $site ] : $own, JSON_PRETTY_PRINT ) ); ?></pre>
		<h2>Last network daily</h2>
		<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;"><?php echo esc_html( wp_json_encode( $last, JSON_PRETTY_PRINT ) ); ?></pre>
		<h2>Opportunity scores</h2>
		<pre style="max-height:280px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px;"><?php echo esc_html( wp_json_encode( $score, JSON_PRETTY_PRINT ) ); ?></pre>
		<p><em>Badge notes:</em> Verified exists on Directory. Listed/Featured education topics are in the conversion pack — badges must not use hidden links. Multisite network admin is not assumed; this MVP is per-site under Settings → SEO Ecosystem.</p>
	</div>
	<?php
}

/** REST status (read-only; soft open when no hub secret). */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'bd-seo-eco/v1',
			'/status',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					if ( current_user_can( 'manage_options' ) ) {
						return true;
					}
					$secret = (string) get_option( 'bds_hub_secret', '' );
					if ( $secret === '' ) {
						return true;
					}
					$hdr = isset( $_SERVER['HTTP_X_BDS_HUB_SECRET'] ) ? (string) $_SERVER['HTTP_X_BDS_HUB_SECRET'] : '';
					return hash_equals( $secret, $hdr );
				},
				'callback'            => function () {
					return rest_ensure_response(
						array(
							'plugin'  => 'BrandDad SEO Ecosystem',
							'version' => BD_SEO_ECO_VER,
							'site'    => bd_seo_eco_site_id(),
							'settings'=> bd_seo_eco_settings(),
							'last_daily' => get_option( 'bd_seo_eco_last_daily', null ),
							'ownership_version' => ( bd_seo_eco_ownership()['version'] ?? null ),
							'autoblog' => class_exists( 'BrandDad_Autoblog', false ),
						)
					);
				},
			)
		);
	}
);
'''

BURST_PHP = r'''<?php
/**
 * Burst publish Autoblog topics with featured images.
 * Hit: /bds-seo-burst-publish-oneshot.php?k=BD_SEO_ECO_2026&limit=5&style=all
 * Optional: style=search_traffic|branddad_conversion|all · requeue=1 · publish=1
 * Gentle batches — keep limit <= 5 to avoid wedging DB.
 */
if ( ! isset( $_GET['k'] ) || $_GET['k'] !== 'BD_SEO_ECO_2026' ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "forbidden\n";
	exit;
}

@set_time_limit( 300 );
@ini_set( 'memory_limit', '256M' );

require __DIR__ . '/wp-load.php';
header( 'Content-Type: application/json; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow', true );

$out = array(
	'ok'     => false,
	'home'   => home_url( '/' ),
	'site'   => function_exists( 'bd_seo_eco_site_id' ) ? bd_seo_eco_site_id() : ( class_exists( 'BDS_AB_Site', false ) ? BDS_AB_Site::id() : null ),
	'limit'  => 0,
	'published' => array(),
	'errors' => array(),
	'notes'  => array(),
);

if ( ! class_exists( 'BDS_AB_Publisher', false ) ) {
	$out['notes'][] = 'BrandDad Autoblog not active';
	echo wp_json_encode( $out, JSON_PRETTY_PRINT );
	exit;
}

$limit = isset( $_GET['limit'] ) ? max( 1, min( 8, absint( $_GET['limit'] ) ) ) : 5;
$style = isset( $_GET['style'] ) ? sanitize_key( wp_unslash( $_GET['style'] ) ) : 'all';
$out['limit'] = $limit;

// Force publish quality settings for burst.
$ab = BrandDad_Autoblog::settings();
$ab['enabled']           = 1;
$ab['status']            = 'publish';
$ab['featured_images']   = 1;
$ab['in_content_images'] = 1;
$ab['prefer_ai']         = 1;
$ab['prefer_ai_images']  = 1;
update_option( 'bds_ab_settings', $ab, false );
$out['notes'][] = 'autoblog forced publish+images';

if ( ! empty( $_GET['requeue'] ) && class_exists( 'BDS_AB_Topics', false ) ) {
	$n = BDS_AB_Topics::requeue_from_seed( false );
	$out['notes'][] = 'requeue_upsert=' . (int) $n;
}

$done = 0;
$guard = 0;
while ( $done < $limit && $guard < $limit * 4 ) {
	$guard++;
	$topic = BDS_AB_Topics::next_queued();
	if ( ! $topic ) {
		$out['notes'][] = 'queue_empty';
		break;
	}
	$tstyle = isset( $topic['content_style'] ) ? $topic['content_style'] : 'search_traffic';
	if ( $style !== 'all' && $tstyle !== $style ) {
		// Skip without consuming forever: temporarily mark skipped_style then continue search via index bump.
		$idx = isset( $topic['_index'] ) ? (int) $topic['_index'] : null;
		if ( null === $idx ) {
			$out['notes'][] = 'style_skip_no_index';
			break;
		}
		$q = BDS_AB_Topics::all();
		if ( isset( $q[ $idx ] ) ) {
			$q[ $idx ]['status'] = 'queued_hold';
			BDS_AB_Topics::save( $q );
		}
		continue;
	}

	$r = BDS_AB_Publisher::publish_topic( $topic, isset( $topic['_index'] ) ? (int) $topic['_index'] : null );
	if ( is_wp_error( $r ) ) {
		$out['errors'][] = array(
			'topic' => $topic['id'] ?? '',
			'error' => $r->get_error_message(),
		);
		// Mark failed so we do not loop forever on same topic.
		if ( isset( $topic['_index'] ) ) {
			$q = BDS_AB_Topics::all();
			$i = (int) $topic['_index'];
			if ( isset( $q[ $i ] ) ) {
				$q[ $i ]['status'] = 'error';
				$q[ $i ]['last_error'] = $r->get_error_message();
				BDS_AB_Topics::save( $q );
			}
		}
		continue;
	}
	$pid = (int) $r;
	$thumb = (int) get_post_thumbnail_id( $pid );
	// Ensure image if missing.
	if ( $thumb < 1 && class_exists( 'BDS_AB_Images', false ) ) {
		$img = BDS_AB_Images::attach_for_post( $pid, $topic );
		$thumb = (int) ( $img['featured'] ?? 0 );
	}
	$out['published'][] = array(
		'post_id'  => $pid,
		'topic'    => $topic['id'] ?? '',
		'title'    => get_the_title( $pid ),
		'url'      => get_permalink( $pid ),
		'style'    => $tstyle,
		'featured' => $thumb,
		'source'   => (string) get_post_meta( $pid, '_bds_ab_image_source', true ),
	);
	$done++;
}

// Restore any queued_hold back to queued.
if ( class_exists( 'BDS_AB_Topics', false ) ) {
	$q = BDS_AB_Topics::all();
	$changed = false;
	foreach ( $q as $i => $t ) {
		if ( isset( $t['status'] ) && 'queued_hold' === $t['status'] ) {
			$q[ $i ]['status'] = 'queued';
			$changed = true;
		}
	}
	if ( $changed ) {
		BDS_AB_Topics::save( $q );
	}
}

$out['ok'] = count( $out['published'] ) > 0 || empty( $out['errors'] );
$out['count'] = count( $out['published'] );
echo wp_json_encode( $out, JSON_PRETTY_PRINT );
'''

INSTALL_BOOT = r'''<?php
/**
 * Install BrandDad SEO Ecosystem MU + ownership JSON + optional autoblog cadence.
 * Hit: /bd-seo-ecosystem-install-oneshot.php?k=BD_SEO_ECO_2026&configure=1
 */
if ( ! isset( $_GET['k'] ) || $_GET['k'] !== 'BD_SEO_ECO_2026' ) {
	http_response_code( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "forbidden\n";
	exit;
}
header( 'Content-Type: application/json; charset=utf-8' );
header( 'X-Robots-Tag: noindex, nofollow', true );
$out = array( 'ok' => false, 'notes' => array(), 'written' => array() );

$b64 = <<<'B64'
__B64_PAYLOAD__
B64;

$json = base64_decode( $b64, true );
if ( false === $json ) {
	$out['notes'][] = 'bad payload';
	echo json_encode( $out, JSON_PRETTY_PRINT );
	exit;
}
$files = json_decode( $json, true );
if ( ! is_array( $files ) || empty( $files['bd-seo-ecosystem-mu.php'] ) ) {
	$out['notes'][] = 'bad files json';
	echo json_encode( $out, JSON_PRETTY_PRINT );
	exit;
}

$root = __DIR__;
for ( $i = 0; $i < 6; $i++ ) {
	if ( file_exists( $root . '/wp-load.php' ) && is_dir( $root . '/wp-content' ) ) {
		break;
	}
	$parent = dirname( $root );
	if ( $parent === $root ) {
		break;
	}
	$root = $parent;
}
$out['wp_root'] = $root;
if ( ! file_exists( $root . '/wp-load.php' ) ) {
	$out['notes'][] = 'wp-load missing';
	echo json_encode( $out, JSON_PRETTY_PRINT );
	exit;
}
$mu = $root . '/wp-content/mu-plugins';
$data_dir = $mu . '/branddad-seo-ecosystem/data';
foreach ( array( $mu, $mu . '/branddad-seo-ecosystem', $data_dir ) as $d ) {
	if ( ! is_dir( $d ) && ! @mkdir( $d, 0755, true ) ) {
		$out['notes'][] = 'mkdir fail ' . $d;
		echo json_encode( $out, JSON_PRETTY_PRINT );
		exit;
	}
}

foreach ( $files as $rel => $content ) {
	if ( $rel === 'bd-seo-ecosystem-mu.php' ) {
		$fp = $mu . '/bd-seo-ecosystem-mu.php';
	} elseif ( strpos( $rel, 'data/' ) === 0 ) {
		$fp = $mu . '/branddad-seo-ecosystem/' . $rel;
	} else {
		$fp = $mu . '/branddad-seo-ecosystem/' . $rel;
	}
	$n = @file_put_contents( $fp, $content );
	$out['written'][] = array( 'path' => $fp, 'bytes' => $n );
}

require_once $root . '/wp-load.php';
if ( function_exists( 'bd_seo_eco_ownership' ) ) {
	bd_seo_eco_ownership();
	$out['notes'][] = 'ownership loaded';
}
if ( ! empty( $_GET['configure'] ) && class_exists( 'BrandDad_Autoblog', false ) ) {
	$ab = BrandDad_Autoblog::settings();
	$ab['enabled'] = 1;
	$ab['status'] = 'publish';
	$ab['featured_images'] = 1;
	$ab['in_content_images'] = 1;
	$ab['cadence'] = '1_per_day';
	$ab['prefer_ai'] = 1;
	update_option( 'bds_ab_settings', $ab, false );
	if ( class_exists( 'BDS_AB_Topics', false ) ) {
		BDS_AB_Topics::requeue_from_seed( false );
		$out['topics'] = count( BDS_AB_Topics::all() );
	}
	if ( class_exists( 'BDS_AB_Cron', false ) ) {
		BDS_AB_Cron::reschedule();
	}
	$out['notes'][] = 'autoblog configured 1_per_day publish';
}
$out['site'] = function_exists( 'bd_seo_eco_site_id' ) ? bd_seo_eco_site_id() : null;
$out['ok'] = true;
$me = __FILE__;
@rename( $me, $me . '.done' );
$out['renamed'] = ! file_exists( $me );
echo json_encode( $out, JSON_PRETTY_PRINT );
'''


def write_mu_and_oneshots():
    DATA.mkdir(parents=True, exist_ok=True)
    (ECO / "bd-seo-ecosystem-mu.php").write_text(MU_PHP, encoding="utf-8")
    ownership = (DATA / "topic-ownership.json").read_text(encoding="utf-8")
    conversion = (DATA / "conversion-topics.json").read_text(encoding="utf-8")
    files = {
        "bd-seo-ecosystem-mu.php": MU_PHP,
        "data/topic-ownership.json": ownership,
        "data/conversion-topics.json": conversion,
    }
    import base64

    payload = base64.b64encode(json.dumps(files).encode("utf-8")).decode("ascii")
    install = INSTALL_BOOT.replace("__B64_PAYLOAD__", payload)
    (ROOT / "bd-seo-ecosystem-install-oneshot.php").write_text(install, encoding="utf-8")
    (ROOT / "bds-seo-burst-publish-oneshot.php").write_text(BURST_PHP, encoding="utf-8")
    # loader copy for repo clarity
    (ROOT / "bd-seo-ecosystem-mu.php").write_text(MU_PHP, encoding="utf-8")
    print("wrote MU + install + burst oneshots")


if __name__ == "__main__":
    write_topics()
    write_mu_and_oneshots()
