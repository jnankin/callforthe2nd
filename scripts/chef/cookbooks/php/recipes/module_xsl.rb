pkg = value_for_platform(
    [ "centos", "redhat", "fedora" ] => {"default" => "php53-pgsql"}, 
    "default" => "php5-xsl"
  )

package pkg do
  action :install
end
