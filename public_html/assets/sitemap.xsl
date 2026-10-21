<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9">

  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <html lang="es">
      <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <title>Sitemap</title>
        <style>
          body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;margin:24px;color:#0f172a;background:#f8fafc}
          .wrap{max-width:1100px;margin:0 auto}
          h1{font-size:20px;margin:0 0 6px 0}
          p{margin:0 0 18px 0;color:#475569;font-size:13px}
          table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden}
          th,td{padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;vertical-align:top}
          th{background:#f1f5f9;text-align:left;color:#334155;font-weight:600}
          tr:last-child td{border-bottom:none}
          a{color:#0f766e;text-decoration:none}
          a:hover{text-decoration:underline}
          .pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#ecfeff;border:1px solid #a5f3fc;color:#155e75;font-size:12px}
          .muted{color:#64748b}
        </style>
      </head>
      <body>
        <div class="wrap">
          <h1>Sitemap</h1>
          <p>Esta vista es solo para humanos. Los buscadores leen el XML igualmente.</p>

          <xsl:choose>
            <xsl:when test="s:sitemapindex">
              <div class="pill">sitemapindex</div>
              <table>
                <thead>
                  <tr>
                    <th>URL</th>
                    <th>Última modificación</th>
                  </tr>
                </thead>
                <tbody>
                  <xsl:for-each select="s:sitemapindex/s:sitemap">
                    <tr>
                      <td><a><xsl:attribute name="href"><xsl:value-of select="s:loc"/></xsl:attribute><xsl:value-of select="s:loc"/></a></td>
                      <td class="muted"><xsl:value-of select="s:lastmod"/></td>
                    </tr>
                  </xsl:for-each>
                </tbody>
              </table>
            </xsl:when>

            <xsl:when test="s:urlset">
              <div class="pill">urlset</div>
              <table>
                <thead>
                  <tr>
                    <th>URL</th>
                    <th>Última modificación</th>
                    <th>Frecuencia</th>
                    <th>Prioridad</th>
                  </tr>
                </thead>
                <tbody>
                  <xsl:for-each select="s:urlset/s:url">
                    <tr>
                      <td><a><xsl:attribute name="href"><xsl:value-of select="s:loc"/></xsl:attribute><xsl:value-of select="s:loc"/></a></td>
                      <td class="muted"><xsl:value-of select="s:lastmod"/></td>
                      <td><xsl:value-of select="s:changefreq"/></td>
                      <td><xsl:value-of select="s:priority"/></td>
                    </tr>
                  </xsl:for-each>
                </tbody>
              </table>
            </xsl:when>

            <xsl:otherwise>
              <p>No se reconoce el formato del sitemap.</p>
            </xsl:otherwise>
          </xsl:choose>
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
