/**
 * Zips `dist/` into `release/<name>-<version>.zip`, the file the registry upload expects
 * ("Publishing & Discovery"). Dependency-free: the zip container is written by hand, entries
 * are deflated with node's zlib.
 */
import { createHash } from 'node:crypto';
import { mkdirSync, readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { deflateRawSync } from 'node:zlib';

const package_json = JSON.parse(readFileSync('package.json', 'utf8'));
const dist = 'dist';
const output = join('release', `${package_json.name}-${package_json.version}.zip`);

function list_files(directory) {
    return readdirSync(directory).flatMap((entry) => {
        const path = join(directory, entry);
        return statSync(path).isDirectory() ? list_files(path) : [path];
    });
}

const crc_table = Array.from({ length: 256 }, (_, n) => {
    let c = n;
    for (let k = 0; k < 8; k++) {
        c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    }
    return c >>> 0;
});

function crc32(buffer) {
    let crc = 0xffffffff;
    for (const byte of buffer) {
        crc = crc_table[(crc ^ byte) & 0xff] ^ (crc >>> 8);
    }
    return (crc ^ 0xffffffff) >>> 0;
}

function dos_time(date) {
    const time = (date.getHours() << 11) | (date.getMinutes() << 5) | (date.getSeconds() >> 1);
    const day = ((date.getFullYear() - 1980) << 9) | ((date.getMonth() + 1) << 5) | date.getDate();
    return { time, day };
}

const files = list_files(dist).sort();
if (files.length === 0) {
    console.error(`${dist}/ is empty — run "npm run build" first.`);
    process.exit(1);
}

const local_parts = [];
const central_parts = [];
let offset = 0;
const { time, day } = dos_time(new Date());

for (const path of files) {
    const name = Buffer.from('dist/' + relative(dist, path).split('\\').join('/'), 'utf8');
    const content = readFileSync(path);
    const compressed = deflateRawSync(content);
    const crc = crc32(content);

    const local = Buffer.alloc(30);
    local.writeUInt32LE(0x04034b50, 0);
    local.writeUInt16LE(20, 4);
    local.writeUInt16LE(0x0800, 6); // UTF-8 names
    local.writeUInt16LE(8, 8); // deflate
    local.writeUInt16LE(time, 10);
    local.writeUInt16LE(day, 12);
    local.writeUInt32LE(crc, 14);
    local.writeUInt32LE(compressed.length, 18);
    local.writeUInt32LE(content.length, 22);
    local.writeUInt16LE(name.length, 26);
    local.writeUInt16LE(0, 28);

    const central = Buffer.alloc(46);
    central.writeUInt32LE(0x02014b50, 0);
    central.writeUInt16LE(20, 4);
    central.writeUInt16LE(20, 6);
    central.writeUInt16LE(0x0800, 8);
    central.writeUInt16LE(8, 10);
    central.writeUInt16LE(time, 12);
    central.writeUInt16LE(day, 14);
    central.writeUInt32LE(crc, 16);
    central.writeUInt32LE(compressed.length, 20);
    central.writeUInt32LE(content.length, 24);
    central.writeUInt16LE(name.length, 28);
    central.writeUInt16LE(0, 30);
    central.writeUInt16LE(0, 32);
    central.writeUInt16LE(0, 34);
    central.writeUInt16LE(0, 36);
    central.writeUInt32LE(0, 38);
    central.writeUInt32LE(offset, 42);

    local_parts.push(local, name, compressed);
    central_parts.push(central, name);
    offset += local.length + name.length + compressed.length;
}

const central_directory = Buffer.concat(central_parts);
const end = Buffer.alloc(22);
end.writeUInt32LE(0x06054b50, 0);
end.writeUInt16LE(0, 4);
end.writeUInt16LE(0, 6);
end.writeUInt16LE(files.length, 8);
end.writeUInt16LE(files.length, 10);
end.writeUInt32LE(central_directory.length, 12);
end.writeUInt32LE(offset, 16);
end.writeUInt16LE(0, 20);

const archive = Buffer.concat([...local_parts, central_directory, end]);
mkdirSync('release', { recursive: true });
writeFileSync(output, archive);
console.log(`${output} (${files.length} files, ${archive.length} bytes, sha256 ${createHash('sha256').update(archive).digest('hex').slice(0, 12)}…)`);
