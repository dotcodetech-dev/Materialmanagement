<?php
/**
 * SEO metadata for AVEON INFOTECH — included from every page's <head>.
 * Applies to the software URL wherever it's crawled or shared.
 */
$seoTitle       = 'AVEON INFOTECH — Software Development, Mobile Apps, College / School / Hostel Management, Aveon GST Flow, Shallon Management, NAAC / IQAC Software';
$seoDescription = 'AVEON INFOTECH — Software Development, Mobile app development, College Management, School Management, Hostel Management, Aveon GST Flow, Shallon Management, NAAC / IQAC Software.';
$seoKeywords    = 'AVEON INFOTECH, Aveon Infotech, Software Development, Mobile app development, College management, School management, Hostel Management, Aveon GST Flow, Shallon Management, NAAC Software, IQAC Software, Material Management, Barcode Inventory';
$seoAuthor      = 'AVEON INFOTECH';
$seoUrl         = current_url();
?>
<meta name="description" content="<?= esc($seoDescription) ?>">
<meta name="keywords" content="<?= esc($seoKeywords) ?>">
<meta name="author" content="<?= esc($seoAuthor) ?>">
<meta name="robots" content="index, follow">
<meta name="language" content="English">
<meta name="revisit-after" content="7 days">

<!-- Open Graph (Facebook, LinkedIn, WhatsApp previews) -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="AVEON INFOTECH">
<meta property="og:title" content="<?= esc($seoTitle) ?>">
<meta property="og:description" content="<?= esc($seoDescription) ?>">
<meta property="og:url" content="<?= esc($seoUrl) ?>">

<!-- Twitter card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= esc($seoTitle) ?>">
<meta name="twitter:description" content="<?= esc($seoDescription) ?>">

<!-- Structured data (Schema.org Organization + services) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "AVEON INFOTECH",
  "url": "<?= esc($seoUrl) ?>",
  "description": "<?= esc($seoDescription) ?>",
  "makesOffer": [
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Software Development" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Mobile App Development" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "College Management Software" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "School Management Software" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Hostel Management Software" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Aveon GST Flow" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Shallon Management" } },
    { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "NAAC / IQAC Software" } }
  ]
}
</script>
