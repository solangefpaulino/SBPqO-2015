<?xml version="1.0" encoding="UTF-8"?>
<!-- ============================================================= -->
<!--  Projeto SBPqO2015, módulo:  Conversão XML para XHTML-Design1 -->
<!--  AUTHOR: Peter Krauss      LICENCE: MIT                       -->
<!--  VERSAO: 1.1               DATE: 2014/08                      -->
<!-- ============================================================= -->

<!-- Convertendo FORMATO 1day XML_STANDARD_S1 de seção de resumos em HTML_STANDARD_F1 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:fn="http://php.net/xsl"
	exclude-result-prefixes="xlink mml fn"  
>

<xsl:template match="sec|subsec">
	<xsl:if test="normalize-space(title) and .//article">
	<div class="{name(.)}">
		<p class="secTitle">
			<xsl:value-of select="title"/>
		</p>
		<xsl:apply-templates select="article"/>
	</div>
    </xsl:if>
</xsl:template>

<xsl:template name="contrib" match="contrib" priority="6">
	<span class="contrib">
		<xsl:value-of select="surname"/><xsl:text>♣</xsl:text>
		<xsl:value-of select="given-names"/><xsl:if test="@corresp">*</xsl:if>
	</span>
</xsl:template>

<xsl:template match="aff|abstract|conclusion">
	<p class="{name(.)}"><xsl:apply-templates/></p>
</xsl:template>

<xsl:template match="article">
	<div class="article" id="{pubid}">
		<hr/>
		<span class="pubid"><xsl:value-of select="pubid"/></span>
        <div class="title">
			<xsl:apply-templates select="title"/>
        </div>
		<div class="contrib-group">
			<xsl:for-each select="contrib-group/node()">
				<xsl:choose>
					<xsl:when test="self::contrib"><xsl:call-template name="contrib"/></xsl:when>
					<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise> 
				</xsl:choose>									
			</xsl:for-each>
		</div>
		<div class="aff-group">
			<xsl:apply-templates select=".//aff"/>
		</div>
		<div class="corresp-group">
			<p class="corresp"><xsl:copy-of select="corresp/node()"/></p>
		</div>
		<xsl:apply-templates select="abstract"/> 
		<xsl:apply-templates select="conclusion"/> 
	</div>
</xsl:template>


</xsl:stylesheet>
