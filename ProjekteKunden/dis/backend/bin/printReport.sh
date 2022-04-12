#!/bin/bash
#
# Print pdf file on a printer
#

function usage {
    echo "usage: $programname [-H host[:port]] [-P printer] pdf-file"
    echo "  -H hostname of the printer or print server (optional with port)"
    echo "  -P name of the printer"
    echo ""
    exit 1
}

# Init variables
host=""
printer=""
file=""

# Parse parameters
while [ $# -gt 0 ]; do
  case "$1" in
    -H)
      shift
      host="$1"
      ;;
    -P)
      shift
      printer="$1"
      ;;
    --help|-h)
      usage
      ;;
    *)
      file="$1"
      if [ -n "$2" ]; then
        >&2 printf "Error: Invalid argument after file name: $2\n"
        exit 1
      fi
      ;;
  esac
  shift
done

# show usage if file parameter missing
if [ -z "$file" ]; then
  usage
fi

# if rlpr installed
if command -v "rlpr" &> /dev/null
then
  # use rlpr (because no CUPs and configuration is needed)
  cmd=`command -v "rlpr"`
  cmd="$cmd --timeout=5"
else
  # else use lpr
  cmd=`command -v "lpr"`
fi

# Build print command
if [ -n "$host" ]; then cmd="$cmd -H$host"; fi
if [ -n "$printer" ]; then cmd="$cmd -P$printer"; fi
cmd="$cmd \"$file\""

# echo $cmd
# echo $cmd >> /tmp/printReports.log

# Run command
eval $cmd
