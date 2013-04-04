#!/usr/bin/perl
use strict;
use warnings;
use Data::Dumper;
use XML::Parser;
my $xmlp = new XML::Parser(Style => 'Tree');

#http://www.omdbapi.com/

while(my $in = <>){
  chomp($in);
	exit if($in =~ /^(q|quit|exit)$/);
	get_info($in);
	}
sub get_info{
	my $term = shift;
	my $search = parse('http://www.omdbapi.com/?s=' . $term);
	if($#$search > 2){
		for(my $i=2; $i <= $#$search; $i+=2){
			my $m = $search->[$i];
			print $i / 2;
			foreach my $c('Year','Type','imdbID','Title'){
				print "\t$c:\t" . $m->[0]->{$c} . "\n";
				}
			}
		my $i;
		do{
			print "Which one? ";
			$i = <>;
			chomp($i);
			if($i =~ /^(q|quit|exit|\D*)$/ || 2*$i > $#$search){
				exit;
				}
		}
		while($i*2 > $#$search);
		$search = $search->[$i*2][0];
		}
	else{
		$search = $search->[2][0];
		}
	my $xml = parse('http://www.omdbapi.com/?i=' . $search->{imdbID});
	$xml = $xml->[2][0];
	#print out results
	print Dumper($xml);
	}


sub parse{
	my $url = shift;
	my $xmlstring = `wget -qO- "$url&r=xml"`;
	#print $xmlstring . "\n";
	die "No api connection!" unless($xmlstring);
	my $obj = $xmlp->parse($xmlstring);
	$obj = $obj->[1];
	return $obj;
	}
