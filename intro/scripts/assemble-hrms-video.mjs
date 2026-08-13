/**
 * Assemble recorded scene clips + title cards into final MP4.
 * Usage: node intro/scripts/assemble-hrms-video.mjs
 */
import { spawnSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const RAW = path.join(ROOT, 'raw');
const ASSETS = path.join(ROOT, 'assets');
const OUT = path.join(ROOT, 'HRMS_Product_Introduction.mp4');

fs.mkdirSync(ASSETS, { recursive: true });

function ffmpeg(args) {
  const r = spawnSync('ffmpeg', args, { encoding: 'utf8', shell: false });
  if (r.status !== 0) {
    const detail = (r.stderr || r.stdout || '').split('\n').slice(-20).join('\n');
    console.error(detail);
    throw new Error(`ffmpeg failed (${r.status}): ${args.slice(0, 6).join(' ')}`);
  }
}

function ffprobeDuration(file) {
  const r = spawnSync(
    'ffprobe',
    ['-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', file],
    { encoding: 'utf8' },
  );
  return parseFloat((r.stdout || '0').trim()) || 0;
}

function makeTitleCard(file, title, subtitle = 'Konnect Nex HRMS', seconds = 3.2) {
  const font = process.env.FFMPEG_FONT || 'C\\:/Windows/Fonts/arial.ttf';
  const escapedTitle = title.replace(/\\/g, '\\\\').replace(/:/g, '\\:').replace(/'/g, "\\'");
  const escapedSub = subtitle.replace(/\\/g, '\\\\').replace(/:/g, '\\:').replace(/'/g, "\\'");
  ffmpeg([
    '-y',
    '-f', 'lavfi',
    '-i', `color=c=0x0B1F33:s=1920x1080:d=${seconds}`,
    '-f', 'lavfi',
    '-i', 'anullsrc=channel_layout=stereo:sample_rate=44100',
    '-vf',
    `drawtext=fontfile='${font}':text='${escapedSub}':fontcolor=0x8FB8D9:fontsize=36:x=(w-text_w)/2:y=380,` +
      `drawtext=fontfile='${font}':text='${escapedTitle}':fontcolor=white:fontsize=58:x=(w-text_w)/2:y=470`,
    '-c:v', 'libx264',
    '-pix_fmt', 'yuv420p',
    '-r', '30',
    '-c:a', 'aac',
    '-shortest',
    '-t', String(seconds),
    file,
  ]);
}

function normalizeClip(input, output) {
  ffmpeg([
    '-y',
    '-i', input,
    '-f', 'lavfi',
    '-i', 'anullsrc=channel_layout=stereo:sample_rate=44100',
    '-vf', 'scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2,fps=30,format=yuv420p',
    '-c:v', 'libx264',
    '-preset', 'medium',
    '-crf', '20',
    '-c:a', 'aac',
    '-b:a', '192k',
    '-shortest',
    '-movflags', '+faststart',
    output,
  ]);
}

const scenes = [
  { id: '01-intro', title: 'Product Introduction' },
  { id: '02-foundation', title: 'HRMS Foundation' },
  { id: '03-employees', title: 'Employee Management' },
  { id: '04-attendance', title: 'Attendance Lifecycle' },
  { id: '05-geo', title: 'Geo-Attendance & Verification' },
  { id: '06-wfh', title: 'Work From Home' },
  { id: '07-leave', title: 'Leave Management' },
  { id: '08-payroll', title: 'Payroll' },
  { id: '09-tax', title: 'Tax & TDS' },
  { id: '10-recruitment', title: 'Recruitment' },
  { id: '11-ess', title: 'Employee Self-Service' },
  { id: '12-manager', title: 'Manager Experience' },
  { id: '13-rbac', title: 'RBAC & Security' },
  { id: '14-workflow', title: 'Workflow & Audit' },
  { id: '15-closing', title: 'Complete HR Lifecycle' },
];

const introCard = path.join(ASSETS, 'title-intro.mp4');
const outroCard = path.join(ASSETS, 'title-outro.mp4');
makeTitleCard(introCard, 'Product Introduction', 'Konnect Nex HRMS', 4.5);
makeTitleCard(
  outroCard,
  'One organization-scoped HR platform',
  'Konnect Nex',
  5,
);

const parts = [introCard];
let total = ffprobeDuration(introCard);

for (const scene of scenes) {
  const webm = path.join(RAW, `${scene.id}.webm`);
  if (!fs.existsSync(webm)) {
    console.log(`Missing clip ${scene.id}.webm — skipping`);
    continue;
  }
  const titleMp4 = path.join(ASSETS, `title-${scene.id}.mp4`);
  const clipMp4 = path.join(ASSETS, `clip-${scene.id}.mp4`);
  makeTitleCard(titleMp4, scene.title);
  normalizeClip(webm, clipMp4);
  parts.push(titleMp4, clipMp4);
  total += ffprobeDuration(titleMp4) + ffprobeDuration(clipMp4);
  console.log(`Ready ${scene.id} (${ffprobeDuration(clipMp4).toFixed(1)}s)`);
}

parts.push(outroCard);
total += ffprobeDuration(outroCard);

const listFile = path.join(ASSETS, 'concat.txt');
fs.writeFileSync(
  listFile,
  parts.map((p) => `file '${p.replace(/\\/g, '/')}'`).join('\n'),
  'utf8',
);

ffmpeg([
  '-y',
  '-f', 'concat',
  '-safe', '0',
  '-i', listFile,
  '-c:v', 'libx264',
  '-preset', 'medium',
  '-crf', '20',
  '-r', '30',
  '-c:a', 'aac',
  '-b:a', '192k',
  '-movflags', '+faststart',
  '-pix_fmt', 'yuv420p',
  OUT,
]);

console.log(`\nFinal video: ${OUT}`);
console.log(`Approx duration: ${(total / 60).toFixed(1)} minutes`);
