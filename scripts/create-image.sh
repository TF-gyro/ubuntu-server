#!/usr/bin/env bash

function help {
  echo ""
  echo "create-disk <disk_size in GB> <location> <disk's name'>"
  exit
}

DiskSize=$1
DiskDir=$2
DiskName=$3

if [ -z "${DiskSize}" ]; then
  echo "Please mention the size of disk image in GB"
  help
fi

if [[ -z "${DiskDir}" ]]; then
  echo "Please mention the location to create image"
  help
fi

if [[ -z "${DiskName}" ]]; then
  DiskName="junction_disk"
fi

# check if destination directory exists, if not then create it
[[ -e "$DiskDir" ]] || mkdir $DiskDir

BlockSize="512K"
BlockCount=$(($DiskSize * 1024 * 1024))
BlockCount=$(($BlockCount / 512))

dd if=/dev/zero of="${DiskDir}/${DiskName}.img" bs=$BlockSize count=$BlockCount

mkfs.ext4 "${DiskDir}/${DiskName}.img"
