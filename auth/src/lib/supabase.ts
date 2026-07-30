import { createClient } from '@supabase/supabase-js';

const url = import.meta.env.VITE_SUPABASE_URL;
const key = import.meta.env.VITE_SUPABASE_ANON_KEY;

if (!url || !key) console.warn('Supabase browser environment variables are not configured.');

const supabase = createClient(url || 'https://invalid.supabase.co', key || 'invalid-key');
export default supabase;
