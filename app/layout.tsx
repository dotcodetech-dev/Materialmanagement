import type { Metadata } from "next";
import "./styles.css";

export const metadata: Metadata = {
  title: {
    default: "MaterialFlow — Barcode-Ready Material Management Software | AVEON Infotech",
    template: "%s | MaterialFlow by AVEON Infotech",
  },
  description: "MaterialFlow is a barcode-ready POS and material management software built by AVEON Infotech. Track inventory with inward/outward movements, generate barcodes, manage stock in real time. Custom software development, mobile app development, and POS solutions by AVEON Infotech.",
  keywords: [
    "POS", "Point of Sale", "POS software", "POS system",
    "Material Management", "material management software", "inventory management",
    "Software Development", "custom software development", "web application development",
    "Mobile App Development", "mobile application development", "app development company",
    "AVEON Infotech", "AVEON", "barcode software", "barcode inventory",
    "stock management", "warehouse management", "inventory tracking",
    "barcode generation", "barcode printing", "inward outward management",
    "material tracking software", "ERP software", "supply chain management",
  ],
  authors: [{ name: "AVEON Infotech", url: "https://aveoninfotech.com" }],
  creator: "AVEON Infotech",
  publisher: "AVEON Infotech",
  applicationName: "MaterialFlow",
  generator: "Next.js",
  referrer: "origin-when-cross-origin",
  robots: {
    index: true,
    follow: true,
    googleBot: { index: true, follow: true, "max-video-preview": -1, "max-image-preview": "large", "max-snippet": -1 },
  },
  openGraph: {
    type: "website",
    locale: "en_IN",
    siteName: "MaterialFlow by AVEON Infotech",
    title: "MaterialFlow — Barcode-Ready POS & Material Management Software",
    description: "Complete material management with barcode support, real-time stock tracking, inward/outward movements, and detailed reports. Built by AVEON Infotech — leaders in POS, software development, and mobile app development.",
    url: "https://materialflow.aveoninfotech.com",
  },
  twitter: {
    card: "summary_large_image",
    title: "MaterialFlow — POS & Material Management Software | AVEON Infotech",
    description: "Barcode-ready inventory and material management. Track stock, generate barcodes, manage inward/outward movements. By AVEON Infotech.",
    creator: "@aveoninfotech",
  },
  alternates: {
    canonical: "https://materialflow.aveoninfotech.com",
  },
  category: "Software",
  other: {
    "apple-mobile-web-app-title": "MaterialFlow",
    "apple-mobile-web-app-capable": "yes",
    "mobile-web-app-capable": "yes",
    "theme-color": "#167d68",
    "msapplication-TileColor": "#167d68",
    "rating": "General",
    "distribution": "Global",
    "revisit-after": "7 days",
    "language": "English",
    "copyright": "AVEON Infotech",
    "author": "AVEON Infotech",
    "designer": "AVEON Infotech",
    "owner": "AVEON Infotech",
    "coverage": "Worldwide",
    "classification": "Business Software, POS, Material Management, Inventory Management",
    "subject": "POS and Material Management Software by AVEON Infotech",
    "topic": "Material Management, POS, Software Development, Mobile App Development",
    "summary": "MaterialFlow is a barcode-ready material management and POS software developed by AVEON Infotech, specialists in custom software development and mobile app development.",
    "abstract": "AVEON Infotech builds MaterialFlow — a complete POS-ready material management system with barcode support, stock tracking, and business intelligence reports.",
    "og:email": "info@aveoninfotech.com",
    "og:locale:alternate": "en_US",
    "article:publisher": "AVEON Infotech",
  },
};

const jsonLd = {
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "SoftwareApplication",
      "name": "MaterialFlow",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web",
      "description": "MaterialFlow is a barcode-ready POS and material management software for tracking inventory, managing inward/outward stock movements, generating barcodes, and producing detailed reports.",
      "url": "https://materialflow.aveoninfotech.com",
      "author": { "@type": "Organization", "name": "AVEON Infotech", "url": "https://aveoninfotech.com" },
      "publisher": { "@type": "Organization", "name": "AVEON Infotech" },
      "offers": { "@type": "Offer", "category": "Material Management Software" },
      "featureList": [
        "Barcode generation and printing (Code128)",
        "Real-time stock tracking with ledger-based accounting",
        "Inward and outward material movement recording",
        "Customer and supplier management",
        "Role-based access control (Admin, Manager, Storekeeper, Viewer)",
        "Inward, Outward, and Stock reports with date, user, and category filters",
        "CSV export and print-ready reports",
        "Multi-label barcode printing with configurable sizes",
        "Company branding and profile customization",
        "POS-ready barcode scanning support",
      ],
      "softwareHelp": { "@type": "WebPage", "url": "https://aveoninfotech.com/materialflow" },
    },
    {
      "@type": "Organization",
      "name": "AVEON Infotech",
      "url": "https://aveoninfotech.com",
      "description": "AVEON Infotech is a technology company specializing in POS systems, material management software, custom software development, and mobile app development. We build scalable, barcode-ready business solutions.",
      "knowsAbout": [
        "POS Software Development",
        "Material Management Systems",
        "Custom Software Development",
        "Mobile App Development",
        "Barcode and Inventory Solutions",
        "Web Application Development",
        "ERP Solutions",
        "Supply Chain Management Software",
      ],
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "AVEON Infotech Services",
        "itemListElement": [
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "POS Software Development", "description": "Custom Point of Sale software with barcode scanning, inventory management, and real-time reporting." } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Material Management Software", "description": "End-to-end material and inventory management systems with barcode support, stock tracking, and movement recording." } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Custom Software Development", "description": "Bespoke web and enterprise software development tailored to business workflows and operational needs." } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Mobile App Development", "description": "Native and cross-platform mobile application development for iOS and Android platforms." } },
        ],
      },
    },
    {
      "@type": "WebApplication",
      "name": "MaterialFlow",
      "url": "https://materialflow.aveoninfotech.com",
      "browserRequirements": "Requires JavaScript. Modern browser (Chrome, Firefox, Safari, Edge).",
      "applicationCategory": "BusinessApplication",
      "applicationSubCategory": "Material Management",
      "creator": { "@type": "Organization", "name": "AVEON Infotech" },
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is MaterialFlow?",
          "acceptedAnswer": { "@type": "Answer", "text": "MaterialFlow is a barcode-ready material management and POS software built by AVEON Infotech. It helps businesses track inventory through inward and outward movements, generate and print barcodes, manage customers, and produce detailed stock reports with filters for date, user, and category." },
        },
        {
          "@type": "Question",
          "name": "What services does AVEON Infotech offer?",
          "acceptedAnswer": { "@type": "Answer", "text": "AVEON Infotech specializes in POS software development, material management solutions, custom software development, mobile app development (iOS and Android), web application development, and enterprise software solutions." },
        },
        {
          "@type": "Question",
          "name": "Does MaterialFlow support barcode scanning?",
          "acceptedAnswer": { "@type": "Answer", "text": "Yes. MaterialFlow supports USB barcode scanners and generates Code128 barcodes. You can auto-generate barcode numbers, print barcode labels in multiple sizes (small, medium, large), and scan items directly into inward or outward transactions." },
        },
        {
          "@type": "Question",
          "name": "Can MaterialFlow generate inventory reports?",
          "acceptedAnswer": { "@type": "Answer", "text": "Yes. MaterialFlow provides Inward Reports, Outward Reports, and Stock Reports. Each report supports filters by date range, user, and item category. Reports can be printed or exported as CSV files with company branding." },
        },
      ],
    },
  ],
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="en">
    <head>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet" />
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
    </head>
    <body>{children}</body>
  </html>;
}
