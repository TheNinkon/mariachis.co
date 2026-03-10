<?php
// Variables
$appUrl = rtrim((string) env('APP_URL', 'https://mariachis.co'), '/');

return [
  "creatorName" => "Mariachis.co",
  "creatorUrl" => $appUrl,
  "templateName" => "Mariachis.co",
  "templateSuffix" => "Plataforma de mariachis en Colombia",
  "templateVersion" => "3.0.0",
  "templateFree" => false,
  "templateDescription" => "Marketplace colombiano para contratar mariachis, gestionar solicitudes y publicar anuncios por ciudad y tipo de evento.",
  "templateKeyword" => "mariachis, mariachis en colombia, serenatas, contratar mariachi, mariachis bogota, mariachis medellin, bodas, cumpleanos",
  "licenseUrl" => $appUrl,
  "livePreview" => $appUrl,
  "productPage" => $appUrl,
  "support" => $appUrl . "/contacto",
  "moreThemes" => $appUrl,
  "ogTitle" => "Mariachis.co | Plataforma de mariachis en Colombia",
  "ogImage" => $appUrl . "/marketplace/assets/logo-wordmark.png",
  "ogType" => "website",
  "documentation" => $appUrl . "/blog",
  "generator" => "",
  "changelog" => $appUrl . "/blog",
  "repository" => $appUrl,
  "gitRepo" => "mariachis-co",
  "gitRepoAccess" => $appUrl,
  "githubFreeUrl" => $appUrl,
  "facebookUrl" => "https://www.facebook.com/mariachis.co",
  "twitterUrl" => "https://x.com/mariachisco",
  "githubUrl" => $appUrl,
  "dribbbleUrl" => $appUrl,
  "instagramUrl" => "https://www.instagram.com/mariachis.co"
];
