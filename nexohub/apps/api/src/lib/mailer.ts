import nodemailer, { type Transporter } from 'nodemailer';
import { env, isTest } from '../config/env';
import { logger } from './logger';

/**
 * Sin SMTP configurado se usa un transporte JSON: el email no se envía,
 * se escribe en el log (útil en desarrollo sin Mailpit).
 */
const transporter: Transporter = env.SMTP_HOST
  ? nodemailer.createTransport({
      host: env.SMTP_HOST,
      port: env.SMTP_PORT,
      secure: env.SMTP_PORT === 465,
      auth: env.SMTP_USER ? { user: env.SMTP_USER, pass: env.SMTP_PASS } : undefined,
    })
  : nodemailer.createTransport({ jsonTransport: true });

interface MailInput {
  to: string;
  subject: string;
  text: string;
  html: string;
}

export async function sendMail(mail: MailInput): Promise<void> {
  if (isTest) return;
  await transporter.sendMail({ from: env.MAIL_FROM, ...mail });
  if (!env.SMTP_HOST) {
    logger.info({ to: mail.to, subject: mail.subject, text: mail.text }, 'Email (modo log)');
  }
}

const escapeHtml = (s: string) =>
  s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!);

export function sendPasswordResetEmail(to: string, name: string, link: string, ttlMinutes: number) {
  const safeName = escapeHtml(name);
  return sendMail({
    to,
    subject: 'Restablecer tu contraseña de NexoHub',
    text:
      `Hola ${name},\n\nRecibimos un pedido para restablecer tu contraseña.\n` +
      `Abrí este enlace (vence en ${ttlMinutes} minutos):\n\n${link}\n\n` +
      'Si no fuiste vos, ignorá este mensaje: tu contraseña no cambia.',
    html: `
      <div style="font-family:system-ui,sans-serif;max-width:480px;margin:auto;color:#111">
        <h2 style="margin:0 0 16px">Restablecer contraseña</h2>
        <p>Hola ${safeName},</p>
        <p>Recibimos un pedido para restablecer tu contraseña. El enlace vence en ${ttlMinutes} minutos.</p>
        <p style="margin:24px 0">
          <a href="${link}" style="background:#e11d48;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none">
            Elegir nueva contraseña
          </a>
        </p>
        <p style="color:#666;font-size:13px">Si no fuiste vos, ignorá este mensaje: tu contraseña no cambia.</p>
      </div>`,
  });
}

