import supabase from './db-client.js';

const cors = (res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
};

export default async function handler(req, res) {
  cors(res);
  if (req.method === 'OPTIONS') return res.status(204).end();

  try {
    const token = req.headers.authorization?.replace('Bearer ', '');
    if (!token) return res.status(401).json({ error: 'Authentication required.' });

    const { data: { user }, error: authError } = await supabase.auth.getUser(token);
    if (authError || !user) return res.status(401).json({ error: 'Your session is invalid or has expired.' });

    if (req.method === 'GET') {
      const { data, error } = await supabase
        .from('user_profiles')
        .select('*')
        .eq('user_id', user.id)
        .maybeSingle();
      if (error) throw error;
      return res.status(200).json(data);
    }

    if (req.method === 'POST') {
      const fullName = String(req.body?.full_name || '').trim();
      if (fullName.length < 2) return res.status(400).json({ error: 'Please enter your full name.' });
      const { data: existing, error: lookupError } = await supabase
        .from('user_profiles')
        .select('id')
        .eq('user_id', user.id)
        .maybeSingle();
      if (lookupError) throw lookupError;
      if (existing) {
        const { data, error } = await supabase
          .from('user_profiles')
          .update({ full_name: fullName, email: user.email, updated_at: new Date().toISOString() })
          .eq('user_id', user.id)
          .select()
          .single();
        if (error) throw error;
        return res.status(200).json(data);
      }
      const { data, error } = await supabase
        .from('user_profiles')
        .insert({
          user_id: user.id,
          full_name: fullName,
          email: user.email,
          role: 'Passenger',
          status: 'Active',
          updated_at: new Date().toISOString(),
        })
        .select()
        .single();
      if (error) throw error;
      return res.status(201).json(data);
    }

    if (req.method === 'PUT') {
      const fullName = String(req.body?.full_name || '').trim();
      if (fullName.length < 2) return res.status(400).json({ error: 'Name must contain at least 2 characters.' });
      const { data, error } = await supabase
        .from('user_profiles')
        .update({ full_name: fullName, updated_at: new Date().toISOString() })
        .eq('user_id', user.id)
        .select()
        .single();
      if (error) throw error;
      return res.status(200).json(data);
    }

    if (req.method === 'DELETE') {
      const { error } = await supabase.from('user_profiles').delete().eq('user_id', user.id);
      if (error) throw error;
      return res.status(200).json({ ok: true });
    }

    return res.status(405).json({ error: 'Method not allowed.' });
  } catch (err) {
    console.error('Profiles API error:', err);
    return res.status(500).json({ error: err.message || 'Unexpected server error.' });
  }
}
