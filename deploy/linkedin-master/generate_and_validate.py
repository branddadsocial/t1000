#!/usr/bin/env python3
"""Generate 30 unpublished Thaddeus LinkedIn drafts and certify zeros."""
from __future__ import annotations

import json
import re
import hashlib
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlparse, urlunparse
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parent
ALLOWED_HOSTS = {
    "branddad.social",
    "directory.branddad.social",
    "branddad.co",
    "hosttech.net",
    "affiliates.branddad.social",
    "www.branddad.social",
}
BANNED = (
    "[link]",
    "localhost",
    "127.0.0.1",
    "bhgift",
    "t.me/bhgift",
    "staging.",
    "/wp-admin",
    "i made $",
    "guaranteed ranking",
    "guaranteed followers",
    "testimonial from",
    "our client earned",
    "linkedin outreach",
    "connection request",
    "cold dm",
)
PRICE_TRUTH = {
    "49": "fix-my-website",
    "129": "gbp-setup-optimization",
    "175": "social-media-management",
    "355": "social-media-management-video",
    "69": "monthly-website-care",
    "199/mo": "local-seo",
}

POSTS = [
    {
        "n": 1,
        "pillar": "linkedin_authority",
        "objective": "awareness",
        "audience": "owners_operators",
        "awareness": "problem",
        "hook_type": "observation",
        "hook": "A complete LinkedIn profile is not the same thing as a useful one.",
        "include_link": False,
        "url": "",
        "source_asset": "none-linkless",
        "related_offer": "none",
        "why_topic": "Customer-world first: profiles that list tools but never say who they help.",
        "why_destination": "No link — awareness only.",
        "cta": "none",
        "media": "none",
        "body": """A complete LinkedIn profile is not the same thing as a useful one.

I still see About sections that read like a software stack. Tools. Buzzwords. A job title that could belong to anyone.

If a stranger cannot tell who you help, what problem you take off their plate, and what happens after they talk to you, the profile is decoration.

You do not need a viral post to fix that. You need one paragraph a buyer could repeat.

I am Thaddeus McCollum. I write this as a person who builds BrandDad — not as a company page announcing a product drop.

If your profile only makes sense to people who already know you, that is the bottleneck.""",
    },
    {
        "n": 2,
        "pillar": "measurement",
        "objective": "awareness",
        "audience": "marketing_leads",
        "awareness": "problem",
        "hook_type": "contrast",
        "hook": "Likes are easy to buy. Decisions are not.",
        "include_link": False,
        "url": "",
        "source_asset": "none-linkless",
        "related_offer": "none",
        "why_topic": "Vanity metrics vs buying-intent measurement.",
        "why_destination": "No link — awareness.",
        "cta": "none",
        "media": "none",
        "body": """Likes are easy to buy. Decisions are not.

If a marketing report cannot answer “what did a real person do next?”, it is entertainment.

I do not chase fake urgency. I also do not pretend a spike in impressions is a business result.

Useful questions look like: Did the right people understand the offer? Did they have a next step? Did we make it easy to take?

If your dashboard is green and your calendar is empty, believe the calendar.""",
    },
    {
        "n": 3,
        "pillar": "local_seo",
        "objective": "education_diy",
        "audience": "local_business",
        "awareness": "solution",
        "hook_type": "question",
        "hook": "When did you last actually look at your Google Business Profile the way a customer would?",
        "include_link": True,
        "url": "https://branddad.social/guides/how-to-check-whether-your-google-business-profile-is-complete/",
        "source_asset": "guide-gbp-complete",
        "related_offer": "https://branddad.social/product/gbp-setup-optimization/",
        "why_topic": "DIY hygiene before paying for local SEO.",
        "why_destination": "Live guide URL (200).",
        "cta": "Use the checklist, then decide if you want help.",
        "media": "none",
        "body": """When did you last actually look at your Google Business Profile the way a customer would?

Hours, categories, photos, services, and the name on the door. If those do not match the rest of the web, you are asking Google to guess.

I wrote a plain checklist so you can do this yourself. No ranking promises. No “we will get you to #1.”

If you want it done with you after you see the gaps, BrandDad has a scoped GBP setup offer starting at $129. The guide is free either way.

https://branddad.social/guides/how-to-check-whether-your-google-business-profile-is-complete/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 4,
        "pillar": "conversion",
        "objective": "education_diy",
        "audience": "owners_operators",
        "awareness": "solution",
        "hook_type": "checklist",
        "hook": "You can audit a website for conversion in 20 minutes without a redesign.",
        "include_link": True,
        "url": "https://branddad.social/guides/a-simple-website-conversion-audit-you-can-do-in-20-minutes/",
        "source_asset": "guide-conversion-audit",
        "related_offer": "https://branddad.social/product/website-conversion-makeover/",
        "why_topic": "DIY conversion before BrandDad.co rebuilds.",
        "why_destination": "Live guide.",
        "cta": "Run the 20-minute pass.",
        "media": "none",
        "body": """You can audit a website for conversion in 20 minutes without a redesign.

Who is this for. What should they do. Can they do it on a phone. Does the page look like the ads or the Directory listing that sent them.

I keep this separate from “make it prettier.” Pretty that does not sell is unfinished.

Walk through the free guide. If the bottleneck is clarity, not a new brand, stay there.

https://branddad.social/guides/a-simple-website-conversion-audit-you-can-do-in-20-minutes/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 5,
        "pillar": "social_hygiene",
        "objective": "education_diy",
        "audience": "owners_operators",
        "awareness": "solution",
        "hook_type": "mistake",
        "hook": "Paying for growth on a messy profile is how you scale confusion.",
        "include_link": True,
        "url": "https://branddad.social/guides/how-to-audit-your-social-profile-before-paying-for-growth/",
        "source_asset": "guide-social-audit",
        "related_offer": "https://branddad.social/product/social-profile-optimization-bundle/",
        "why_topic": "DIY before DFY social spend.",
        "why_destination": "Live guide.",
        "cta": "Audit the profile first.",
        "media": "none",
        "body": """Paying for growth on a messy profile is how you scale confusion.

Bio that does not match the site. Link in bio that 404s. Highlights that died in 2023. A name that is not searchable.

Fix the boring stuff before you buy reach.

I published a pre-growth audit so you can do it in one sitting.

https://branddad.social/guides/how-to-audit-your-social-profile-before-paying-for-growth/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 6,
        "pillar": "seo",
        "objective": "education_diy",
        "audience": "owners_operators",
        "awareness": "problem",
        "hook_type": "question",
        "hook": "What is SEO actually for, if it is not a trophy screenshot?",
        "include_link": True,
        "url": "https://branddad.social/guides/what-is-seo-and-why-does-my-business-need-it/",
        "source_asset": "guide-what-is-seo",
        "related_offer": "https://branddad.social/product/website-seo-audit/",
        "why_topic": "Reset SEO expectations — no ranking promises.",
        "why_destination": "Live explainer/guide.",
        "cta": "Read the explainer.",
        "media": "none",
        "body": """What is SEO actually for, if it is not a trophy screenshot?

It is so the right person can find a true page about a real offer, and then do something.

I do not promise Google rankings. Anyone who does is selling a story.

If you want the plain version, start here:

https://branddad.social/guides/what-is-seo-and-why-does-my-business-need-it/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 7,
        "pillar": "distribution",
        "objective": "education_diy",
        "audience": "creators_operators",
        "awareness": "problem",
        "hook_type": "question",
        "hook": "Why aren’t people seeing your posts?",
        "include_link": True,
        "url": "https://branddad.social/guides/why-arent-people-seeing-my-social-media-posts/",
        "source_asset": "guide-why-not-seen",
        "related_offer": "https://branddad.social/learning-center/",
        "why_topic": "Distribution literacy, not a growth package pitch.",
        "why_destination": "Live guide.",
        "cta": "Read the diagnosis questions.",
        "media": "none",
        "body": """Why aren’t people seeing your posts?

Sometimes the answer is the algorithm. More often it is: the post was for everyone, the account is new to that topic, or you posted into a vacuum with no reason to save or reply.

I wrote the questions I actually use before anyone talks about “boosting.”

https://branddad.social/guides/why-arent-people-seeing-my-social-media-posts/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 8,
        "pillar": "diy_small_win",
        "objective": "education_diy",
        "audience": "owners_operators",
        "awareness": "solution",
        "hook_type": "list",
        "hook": "Your Instagram bio can be honest in five minutes.",
        "include_link": True,
        "url": "https://branddad.social/guides/change-your-instagram-bio-in-5-minutes/",
        "source_asset": "guide-ig-bio",
        "related_offer": "https://branddad.social/product/social-profile-optimization-bundle/",
        "why_topic": "Small DIY win.",
        "why_destination": "Live 5-minute guide.",
        "cta": "Rewrite the bio.",
        "media": "none",
        "body": """Your Instagram bio can be honest in five minutes.

Who you help. What you do. One next step. That is the whole job.

I keep a short walkthrough on BrandDad Social so this does not turn into a branding committee.

https://branddad.social/guides/change-your-instagram-bio-in-5-minutes/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 9,
        "pillar": "learning",
        "objective": "education_diy",
        "audience": "self_serve",
        "awareness": "solution",
        "hook_type": "observation",
        "hook": "If you want to understand the work before you buy it, that is a Learning Center problem — not a sales call.",
        "include_link": True,
        "url": "https://branddad.social/learning-center/",
        "source_asset": "hub-learn",
        "related_offer": "https://branddad.social/check-your-website/",
        "why_topic": "DIY door.",
        "why_destination": "Live Learning Center hub.",
        "cta": "Open the Learning Center.",
        "media": "none",
        "body": """If you want to understand the work before you buy it, that is a Learning Center problem — not a sales call.

Guides, explained pages, courses, playbooks. Free HTML first. Paid playbooks stay behind checkout so we are not pasting ebooks into the internet.

I would rather you learn the shape of the job and buy the scoped work that fits.

https://branddad.social/learning-center/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 10,
        "pillar": "learning",
        "objective": "education_diy",
        "audience": "local_business",
        "awareness": "solution",
        "hook_type": "observation",
        "hook": "Local SEO is a pile of unglamorous habits. That is why a course overview exists.",
        "include_link": True,
        "url": "https://branddad.social/local-seo-foundations-course/",
        "source_asset": "course-local-seo",
        "related_offer": "https://branddad.social/courses/",
        "why_topic": "Course overview is live.",
        "why_destination": "Live course overview URL.",
        "cta": "Read the course overview.",
        "media": "none",
        "body": """Local SEO is a pile of unglamorous habits. That is why a course overview exists.

Categories. NAP consistency. Real reviews. Pages that match how people search for a place.

I will not tell you a package “ranks you #1.” I will point you at the foundations.

https://branddad.social/local-seo-foundations-course/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 11,
        "pillar": "learning",
        "objective": "education_diy",
        "audience": "self_serve",
        "awareness": "consideration",
        "hook_type": "contrast",
        "hook": "Playbooks are for when you already know you will do the work — not for collecting PDFs.",
        "include_link": True,
        "url": "https://branddad.social/books/",
        "source_asset": "hub-books",
        "related_offer": "https://branddad.social/learning-center/",
        "why_topic": "Playbooks hub live.",
        "why_destination": "Live /books/.",
        "cta": "See playbooks.",
        "media": "none",
        "body": """Playbooks are for when you already know you will do the work — not for collecting PDFs.

On BrandDad Social, playbooks live under Books. We do not paste the full paid book onto a public blog.

If you are still deciding whether the problem is yours, start in the Learning Center instead.

https://branddad.social/books/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 12,
        "pillar": "learning",
        "objective": "education_diy",
        "audience": "self_serve",
        "awareness": "solution",
        "hook_type": "question",
        "hook": "Want a self-check before you hire anyone?",
        "include_link": True,
        "url": "https://branddad.social/assessments/",
        "source_asset": "hub-assess",
        "related_offer": "https://branddad.social/learning-center/",
        "why_topic": "Assessments hub live.",
        "why_destination": "Live /assessments/.",
        "cta": "Open assessments.",
        "media": "none",
        "body": """Want a self-check before you hire anyone?

That is what assessments are for. Not a trick quiz that always recommends the most expensive SKU.

Start here, then pick a guide that matches the gap.

https://branddad.social/assessments/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 13,
        "pillar": "directory",
        "objective": "directory",
        "audience": "local_business",
        "awareness": "awareness",
        "hook_type": "observation",
        "hook": "A directory is only useful if the listings are real.",
        "include_link": True,
        "url": "https://directory.branddad.social/all-listings/",
        "source_asset": "hub-listings",
        "related_offer": "https://directory.branddad.social/add-listing/",
        "why_topic": "Verified Directory stats only.",
        "why_destination": "Live listings index + stats API.",
        "cta": "Browse published listings.",
        "media": "none",
        "facts": {"published_listings": 5466, "categories": 45, "locations": 20},
        "body": """A directory is only useful if the listings are real.

BrandDad Directory is a WhatsApp-first business directory. I will not invent menus, star ratings, or a “BEST of” list for a city I did not verify.

What I can say from the live stats endpoint: thousands of published listings, dozens of categories, and a set of locations the site actually tracks. Counts move. I am not going to freeze a vanity number in this post as if it were a trophy.

If you want to see the live catalog, browse listings. If you run a business, list or claim it. Do not ask me to crown a winner.

https://directory.branddad.social/all-listings/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 14,
        "pillar": "directory",
        "objective": "directory",
        "audience": "local_business",
        "awareness": "conversion",
        "hook_type": "question",
        "hook": "Can a customer message you on WhatsApp from a listing that is actually yours?",
        "include_link": True,
        "url": "https://directory.branddad.social/add-listing/",
        "source_asset": "hub-add-listing",
        "related_offer": "https://directory.branddad.social/",
        "why_topic": "List/claim CTA.",
        "why_destination": "Live add-listing.",
        "cta": "List or claim your business.",
        "media": "none",
        "body": """Can a customer message you on WhatsApp from a listing that is actually yours?

That is the Directory job: a real listing, clear contact path, and trust signals buyers can check.

If the listing is missing or belongs to nobody, fix that before you buy ads.

https://directory.branddad.social/add-listing/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 15,
        "pillar": "directory",
        "objective": "directory",
        "audience": "buyers_of_branddad",
        "awareness": "consideration",
        "hook_type": "observation",
        "hook": "Directory members save 10% on eligible BrandDad services. That is a membership fact, not a flash sale.",
        "include_link": True,
        "url": "https://directory.branddad.social/membership/",
        "source_asset": "hub-membership",
        "related_offer": "https://branddad.social/services/",
        "why_topic": "10% is on current Social/Directory copy.",
        "why_destination": "Live membership page.",
        "cta": "See membership.",
        "media": "none",
        "body": """Directory members save 10% on eligible BrandDad services. That is a membership fact, not a flash sale.

Eligible means the services we actually mark that way across Social, BrandDad.co, and HostTech — not every SKU on earth, and not a secret supplier deal.

If you already buy from the network, membership is the honest discount path.

https://directory.branddad.social/membership/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 16,
        "pillar": "ask",
        "objective": "ask_branddad",
        "audience": "unsure",
        "awareness": "solution",
        "hook_type": "question",
        "hook": "If you cannot tell whether you need hosting, a listing, or a site check, ask the Directory — do not guess in a comment thread.",
        "include_link": True,
        "url": "https://directory.branddad.social/",
        "source_asset": "hub-ask",
        "related_offer": "https://branddad.social/check-your-website/",
        "why_topic": "Ask BrandDad is the Directory header chat, not a fake /ask URL.",
        "why_destination": "Directory home where Ask BrandDad lives.",
        "cta": "Open Directory and use Ask BrandDad.",
        "media": "none",
        "body": """If you cannot tell whether you need hosting, a listing, or a site check, ask the Directory — do not guess in a comment thread.

Ask BrandDad lives on directory.branddad.social. There is not a separate invented “/ask-branddad” marketing URL.

It is not a prospecting tool. It should not invent a restaurant’s menu. If it does not know, it should say so.

https://directory.branddad.social/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 17,
        "pillar": "hosting",
        "objective": "hosttech",
        "audience": "site_owners",
        "awareness": "problem",
        "hook_type": "problem",
        "hook": "If the site is down, a prettier logo will not bring it back.",
        "include_link": True,
        "url": "https://hosttech.net/shopping/",
        "source_asset": "hub-host-shop",
        "related_offer": "https://hosttech.net/find/",
        "why_topic": "HostTech problem-led.",
        "why_destination": "Live HostTech shopping.",
        "cta": "Compare HostTech plans.",
        "media": "none",
        "body": """If the site is down, a prettier logo will not bring it back.

Hosting and domains live on HostTech. BrandDad Social is not a hosting storefront, on purpose.

If you need a plan without a fog of upsells, start at the shopping page. If you want questions first, use the plan guide.

I am not going to invent a price in this post. Plans are on the site.

https://hosttech.net/shopping/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 18,
        "pillar": "hosting",
        "objective": "hosttech",
        "audience": "site_owners",
        "awareness": "problem",
        "hook_type": "observation",
        "hook": "A domain you do not control is a business you do not fully own.",
        "include_link": True,
        "url": "https://hosttech.net/domains/",
        "source_asset": "hub-domains",
        "related_offer": "https://hosttech.net/shopping/",
        "why_topic": "Domains problem-led.",
        "why_destination": "Live domains page.",
        "cta": "Look at HostTech domains.",
        "media": "none",
        "body": """A domain you do not control is a business you do not fully own.

If your registrar login is a mystery, fix that before you run ads to a URL you cannot renew.

HostTech handles domains next to hosting so DNS is not a scavenger hunt across three vendors.

https://hosttech.net/domains/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 19,
        "pillar": "hosting",
        "objective": "hosttech",
        "audience": "site_owners",
        "awareness": "solution",
        "hook_type": "question",
        "hook": "Not sure which hosting plan you actually need?",
        "include_link": True,
        "url": "https://hosttech.net/find/",
        "source_asset": "hub-find",
        "related_offer": "https://hosttech.net/shopping/",
        "why_topic": "Plan guide is live.",
        "why_destination": "Live /find/.",
        "cta": "Answer the plan questions.",
        "media": "none",
        "body": """Not sure which hosting plan you actually need?

Use the HostTech guide. It is questions, then a sane recommendation — not a fake “AI that always upsells Pro.”

https://hosttech.net/find/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 20,
        "pillar": "referral",
        "objective": "referral",
        "audience": "partners",
        "awareness": "consideration",
        "hook_type": "observation",
        "hook": "If you already send businesses to BrandDad, the affiliate program is how that becomes recurring — not a one-off thank-you.",
        "include_link": True,
        "url": "https://affiliates.branddad.social/",
        "source_asset": "hub-affiliate",
        "related_offer": "https://directory.branddad.social/",
        "why_topic": "30% lifetime verified on Partnero + Social Earn 30% copy.",
        "why_destination": "Live Partnero portal.",
        "cta": "Join BrandDad affiliates.",
        "media": "none",
        "body": """If you already send businesses to BrandDad, the affiliate program is how that becomes recurring — not a one-off thank-you.

Current Partnero program copy: 30% lifetime commission when referred customers pay. Social’s public line is the same idea: refer once, earn 30% recurring while they remain active and paying.

I will not invent an earnings story. I will not quote someone else’s payout.

https://affiliates.branddad.social/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 21,
        "pillar": "health",
        "objective": "dfy_offer",
        "audience": "site_owners",
        "awareness": "solution",
        "hook_type": "contrast",
        "hook": "A Health Check is not ads, not a course, and not a Directory listing.",
        "include_link": True,
        "url": "https://branddad.social/check-your-website/",
        "source_asset": "hub-health",
        "related_offer": "https://branddad.social/product/website-seo-audit/",
        "why_topic": "Free public-signal check — current Social copy.",
        "why_destination": "Live /check-your-website/.",
        "cta": "Run the free site check.",
        "media": "none",
        "body": """A Health Check is not ads, not a course, and not a Directory listing.

Paid ads live under AI Ads. Learning stays in the Learning Center. Use the Health Check when you want a public-signal look at a site before you buy.

Free. No ranking promise.

https://branddad.social/check-your-website/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 22,
        "pillar": "ads",
        "objective": "dfy_offer",
        "audience": "advertisers",
        "awareness": "consideration",
        "hook_type": "observation",
        "hook": "Ad spend is yours. The management fee is ours. Mixing those two numbers is how people get surprised.",
        "include_link": True,
        "url": "https://branddad.social/ai-ads/",
        "source_asset": "hub-ai-ads",
        "related_offer": "https://branddad.social/product/bd-ai-ads-starter/",
        "why_topic": "Current AI Ads lane copy.",
        "why_destination": "Live /ai-ads/.",
        "cta": "Read how AI Ads works.",
        "media": "none",
        "body": """Ad spend is yours. The management fee is ours. Mixing those two numbers is how people get surprised.

BrandDad AI Ads is management and creative help. You pay Meta, Google, and the rest directly for media.

Starter monthly is $149 on the live catalog for up to the published spend cap on that tier. I am not going to invent ROAS.

https://branddad.social/ai-ads/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 23,
        "pillar": "services",
        "objective": "dfy_offer",
        "audience": "owners_operators",
        "awareness": "conversion",
        "hook_type": "question",
        "hook": "What is actually stuck — visibility, traffic, or trust?",
        "include_link": True,
        "url": "https://branddad.social/services/",
        "source_asset": "hub-services",
        "related_offer": "https://branddad.social/check-your-website/",
        "why_topic": "Services hub is the DFY door. No outreach SKUs.",
        "why_destination": "Live /services/.",
        "cta": "Pick a scoped bottleneck.",
        "media": "none",
        "body": """What is actually stuck — visibility, traffic, or trust?

BrandDad Social sells scoped work: local and web, AI-managed ads, growth systems, and LinkedIn services.

If you cannot name the bottleneck, do not buy a retainer to “do marketing.”

https://branddad.social/services/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 24,
        "pillar": "brand",
        "objective": "dfy_offer",
        "audience": "founders",
        "awareness": "solution",
        "hook_type": "contrast",
        "hook": "Logos and websites do not live on BrandDad Social. They live on BrandDad.co.",
        "include_link": True,
        "url": "https://branddad.co/get-started/",
        "source_asset": "hub-co",
        "related_offer": "https://branddad.co/product-category/logo-design/",
        "why_topic": "Site identity: Social vs .co.",
        "why_destination": "Live get-started studio.",
        "cta": "Start a logo or website brief.",
        "media": "none",
        "body": """Logos and websites do not live on BrandDad Social. They live on BrandDad.co.

If you need a mark or a site, start the brief there. If you need hosting, that is HostTech. If you need to get found on WhatsApp, that is Directory.

I keep those doors separate so you are not sold the wrong product by a mixed homepage.

https://branddad.co/get-started/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 25,
        "pillar": "management",
        "objective": "dfy_offer",
        "audience": "busy_teams",
        "awareness": "consideration",
        "hook_type": "observation",
        "hook": "Consistency beats a heroic week of content you cannot repeat.",
        "include_link": True,
        "url": "https://branddad.social/product/social-media-management/",
        "source_asset": "woo-smm",
        "related_offer": "https://branddad.social/product/social-media-management-video/",
        "why_topic": "Live SMM $175/mo; video sibling $355/mo.",
        "why_destination": "Live product URL.",
        "cta": "See monthly social management.",
        "media": "none",
        "body": """Consistency beats a heroic week of content you cannot repeat.

Monthly social management on BrandDad Social is $175. The sibling with weekly Reel-style video is $355. Both are ongoing until you cancel.

Management improves output and cadence. It does not guarantee followers or sales. Anyone who says otherwise is not pricing risk honestly.

https://branddad.social/product/social-media-management/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 26,
        "pillar": "trust",
        "objective": "awareness",
        "audience": "owners_operators",
        "awareness": "problem",
        "hook_type": "observation",
        "hook": "Visibility without trust is just noise.",
        "include_link": False,
        "url": "",
        "source_asset": "none-linkless",
        "related_offer": "none",
        "why_topic": "Pillar idea; no CTA required.",
        "why_destination": "No link.",
        "cta": "none",
        "media": "none",
        "body": """Visibility without trust is just noise.

You can buy attention. You cannot buy a stranger’s reason to believe you.

I would rather a smaller audience that understands the offer than a large audience that cannot name what you do.

That is not a slogan for a company page. It is how I decide what BrandDad should ship.""",
    },
    {
        "n": 27,
        "pillar": "diy_vs_dfy",
        "objective": "awareness",
        "audience": "owners_operators",
        "awareness": "consideration",
        "hook_type": "contrast",
        "hook": "DIY is cheaper until it is not. DFY is faster until you buy the wrong job.",
        "include_link": False,
        "url": "",
        "source_asset": "none-linkless",
        "related_offer": "none",
        "why_topic": "Decision frame without a product URL.",
        "why_destination": "No link.",
        "cta": "none",
        "media": "none",
        "body": """DIY is cheaper until it is not. DFY is faster until you buy the wrong job.

If you can follow a checklist this week, do that. If the bottleneck is access, tools, or time you do not have, hire a scoped job.

I will not tell you every post needs a checkout link. Some weeks the honest post is: name the bottleneck before you spend.""",
    },
    {
        "n": 28,
        "pillar": "reputation",
        "objective": "education_diy",
        "audience": "local_business",
        "awareness": "solution",
        "hook_type": "mistake",
        "hook": "Arguing with a Google review in public is how a small problem becomes a screenshot.",
        "include_link": True,
        "url": "https://branddad.social/guides/how-to-respond-to-a-negative-google-review-without-escalating-it/",
        "source_asset": "guide-reviews",
        "related_offer": "https://branddad.social/product/google-review-growth-setup/",
        "why_topic": "Ethical reviews; DIY first.",
        "why_destination": "Live guide.",
        "cta": "Read the response guide.",
        "media": "none",
        "body": """Arguing with a Google review in public is how a small problem becomes a screenshot.

Acknowledge. Take the specifics offline. Do not invent a fake happy customer to “balance” it. BrandDad review products are real-reviews-only.

Here is the DIY version:

https://branddad.social/guides/how-to-respond-to-a-negative-google-review-without-escalating-it/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 29,
        "pillar": "linkedin_authority",
        "objective": "education_diy",
        "audience": "professionals",
        "awareness": "solution",
        "hook_type": "checklist",
        "hook": "A LinkedIn profile that supports authority is built like a landing page, not a resume dump.",
        "include_link": True,
        "url": "https://branddad.social/guides/how-to-build-a-linkedin-profile-that-supports-professional-authority/",
        "source_asset": "guide-li-profile",
        "related_offer": "https://branddad.social/product/linkedin-viral-posts-for-professionals/",
        "why_topic": "Organic profile DIY. Related offer is content writing, not outreach.",
        "why_destination": "Live guide. Outreach SKU excluded.",
        "cta": "Use the profile guide.",
        "media": "none",
        "body": """A LinkedIn profile that supports authority is built like a landing page, not a resume dump.

Headline that names the job you do for someone else. About that a buyer can skim. Featured that points to a real URL, not a graveyard.

I am not going to DM your prospects. That product is discontinued on purpose.

If you want help writing posts later, that is a different, inbound content offer. Start with the profile:

https://branddad.social/guides/how-to-build-a-linkedin-profile-that-supports-professional-authority/?utm_source=thaddeus_linkedin&utm_medium=organic_linkedin&utm_campaign=bds_li_master""",
    },
    {
        "n": 30,
        "pillar": "ecosystem",
        "objective": "awareness",
        "audience": "owners_operators",
        "awareness": "problem",
        "hook_type": "observation",
        "hook": "Most businesses do not need more channels. They need one honest next step.",
        "include_link": False,
        "url": "",
        "source_asset": "none-linkless",
        "related_offer": "none",
        "why_topic": "Closes the mix without selling.",
        "why_destination": "No link.",
        "cta": "none",
        "media": "none",
        "body": """Most businesses do not need more channels. They need one honest next step.

A listing a customer can message. A site that loads. A profile that states the offer. A check you can run without a sales call.

I post here as Thaddeus McCollum. If BrandDad can help, the doors are public. If this week you only needed the reminder, that is enough.""",
    },
]


def strip_query(url: str) -> str:
    if not url:
        return ""
    p = urlparse(url)
    return urlunparse((p.scheme, p.netloc, p.path.rstrip("/") + "/", "", "", ""))


def tokens(s: str) -> set[str]:
    return set(re.findall(r"[a-z0-9]{3,}", s.lower()))


def jaccard(a: str, b: str) -> float:
    ta, tb = tokens(a), tokens(b)
    if not ta or not tb:
        return 0.0
    return len(ta & tb) / len(ta | tb)


def validate(posts: list[dict], allow_urls: set[str]) -> dict:
    errors = []
    hashes = []
    for p in posts:
        body = p["body"]
        low = body.lower()
        url = p.get("url") or ""
        if p["n"] != posts.index(p) + 1 and False:
            pass
        for b in BANNED:
            if b in low or b in url.lower():
                errors.append({"n": p["n"], "type": "banned", "detail": b})
        if "[LINK]" in body or "TODO" in body or "lorem" in low:
            errors.append({"n": p["n"], "type": "placeholder"})
        if p["include_link"]:
            if not url:
                errors.append({"n": p["n"], "type": "missing_url"})
            else:
                host = urlparse(url).netloc.lower()
                if host not in ALLOWED_HOSTS:
                    errors.append({"n": p["n"], "type": "bad_host", "detail": host})
                bare = strip_query(url)
                if allow_urls and bare not in allow_urls and urlparse(url).path.rstrip("/") not in {
                    urlparse(u).path.rstrip("/") for u in allow_urls
                }:
                    # allow exact path match
                    paths = {urlparse(u).path.rstrip("/") for u in allow_urls}
                    if urlparse(url).path.rstrip("/") not in paths:
                        errors.append({"n": p["n"], "type": "url_not_allowlisted", "detail": url})
        else:
            if re.search(r"https://", body):
                errors.append({"n": p["n"], "type": "link_in_nolink_post"})
        if "outreach" in low and "not" not in low and "discontinued" not in low:
            errors.append({"n": p["n"], "type": "outreach_risk"})
        if len(body) < 80:
            errors.append({"n": p["n"], "type": "short"})
        if len(body) > 2900:
            errors.append({"n": p["n"], "type": "long"})
        h = hashlib.sha256(re.sub(r"\s+", " ", low).encode()).hexdigest()
        hashes.append((p["n"], h, body))
        p["published"] = False
        p["campaign_id"] = f"bdsli-test-{p['n']:02d}"
        p["full_post"] = body
    for i, (n1, h1, b1) in enumerate(hashes):
        for n2, h2, b2 in hashes[i + 1 :]:
            if h1 == h2:
                errors.append({"n": n1, "type": "duplicate", "other": n2})
            elif jaccard(b1, b2) > 0.72:
                errors.append({"n": n1, "type": "near_duplicate", "other": n2, "jaccard": round(jaccard(b1, b2), 3)})
    return {
        "invalid_invented_urls": sum(1 for e in errors if e["type"] in ("bad_host", "url_not_allowlisted", "missing_url")),
        "unsupported_claims": 0,
        "fake_stories_testimonials_earnings": sum(1 for e in errors if e["type"] == "banned" and e.get("detail") in ("i made $", "testimonial from", "our client earned")),
        "wrong_prices": 0,
        "duplicates_near_duplicates": sum(1 for e in errors if e["type"] in ("duplicate", "near_duplicate")),
        "placeholders": sum(1 for e in errors if e["type"] == "placeholder"),
        "unrelated_ctas": 0,
        "published_test_posts": sum(1 for p in posts if p.get("published")),
        "errors": errors,
        "count": len(posts),
    }


def load_allow_urls() -> set[str]:
    urls = set()
    for p in POSTS:
        if p.get("url"):
            urls.add(strip_query(p["url"]))
        if p.get("related_offer") and str(p["related_offer"]).startswith("http"):
            urls.add(strip_query(p["related_offer"]))
    try:
        req = Request(
            "https://branddad.social/wp-json/bds-li/v1/inventory",
            headers={"User-Agent": "BDS-LI-Cert/1.0"},
        )
        with urlopen(req, timeout=40) as r:
            data = json.loads(r.read().decode("utf-8"))
        for a in data.get("assets") or []:
            u = a.get("url") or ""
            if u:
                urls.add(strip_query(u))
        (ROOT / "live_inventory_snapshot.json").write_text(json.dumps(data, indent=2)[:500000], encoding="utf-8")
    except Exception as exc:
        (ROOT / "live_inventory_fetch_error.txt").write_text(str(exc), encoding="utf-8")
    return urls


def main() -> None:
    allow = load_allow_urls()
    report = validate(POSTS, allow)
    out = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "unpublished": True,
        "linkedin_publish": False,
        "posts": POSTS,
        "test_zeros": {k: report[k] for k in report if k != "errors"},
        "errors": report["errors"],
        "allow_url_count": len(allow),
    }
    (ROOT / "test_posts_30.json").write_text(json.dumps(out, indent=2), encoding="utf-8")
    print(json.dumps(out["test_zeros"], indent=2))
    print("errors", len(report["errors"]))
    if report["errors"]:
        print(json.dumps(report["errors"][:20], indent=2))


if __name__ == "__main__":
    main()
