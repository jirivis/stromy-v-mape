<?php
// Krovak inverse and seven-parameter Bessel -> WGS84 conversion, matching
// the bundled proj4 definition used by the import. Does not modify source data.
function ptJtskPoint(mixed $east,mixed $north):?array{
 if(!is_numeric($east)||!is_numeric($north))return null;
 $east=(float)$east;$north=(float)$north;
 // Accept either negative EPSG:5514 E/N or conventional positive Y/X.
 if($east>0&&$north>0){$east=-$east;$north=-$north;}
 if(!is_finite($east)||!is_finite($north)||$east>=0||$north>=0)return null;
 $a=6377397.155;$es=.006674372230614;$e=sqrt($es);$fi=deg2rad(49.5);$alpha=sqrt(1+$es*pow(cos($fi),4)/(1-$es));$u0=asin(sin($fi)/$alpha);
 $k=tan($u0/2+M_PI/4)/pow(tan($fi/2+M_PI/4),$alpha)*pow((1+$e*sin($fi))/(1-$e*sin($fi)),$alpha*$e/2);
 $s0=1.37008346281555;$n=sin($s0);$ro0=.9999*$a*sqrt(1-$es)/(1-$es*pow(sin($fi),2))/tan($s0);$ad=M_PI/2-1.04216856380474;
 $rho=hypot($east,$north);if($rho<1)return null;$h=atan2(-$east,-$north)/$n;$s=2*(atan(pow($ro0/$rho,1/$n)*tan($s0/2+M_PI/4))-M_PI/4);
 $u=asin(cos($ad)*sin($s)-sin($ad)*cos($s)*cos($h));$v=asin(cos($s)*sin($h)/cos($u));$lon=deg2rad(24.8333333333333)-$v/$alpha;$lat=$u;
 for($i=0;$i<20;$i++){$next=2*(atan(pow($k,-1/$alpha)*pow(tan($u/2+M_PI/4),1/$alpha)*pow((1+$e*sin($lat))/(1-$e*sin($lat)),$e/2))-M_PI/4);if(abs($next-$lat)<1e-12){$lat=$next;break;}$lat=$next;}
 $N=$a/sqrt(1-$es*sin($lat)**2);$x=$N*cos($lat)*cos($lon);$y=$N*cos($lat)*sin($lon);$z=$N*(1-$es)*sin($lat);
 $r=M_PI/(180*3600);$rx=4.99821*$r;$ry=1.58676*$r;$rz=5.2611*$r;$scale=1+3.56e-6;
 [$x,$y,$z]=[570.69+$scale*($x-$rz*$y+$ry*$z),85.69+$scale*($rz*$x+$y-$rx*$z),462.84+$scale*(-$ry*$x+$rx*$y+$z)];
 $wgsEs=.0066943799901413165;$lon=atan2($y,$x);$p=hypot($x,$y);$lat=atan2($z,$p*(1-$wgsEs));
 for($i=0;$i<15;$i++){$N=6378137/sqrt(1-$wgsEs*sin($lat)**2);$lat=atan2($z+$wgsEs*$N*sin($lat),$p);}
 $lon=rad2deg($lon);$lat=rad2deg($lat);if(!is_finite($lon)||!is_finite($lat)||$lon<11||$lon>24||$lat<47||$lat>52)return null;
 return ['type'=>'Point','coordinates'=>[$lon,$lat]];
}
