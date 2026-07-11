// Configuration des informations de contact
export const CONTACT_CONFIG = {
  // Numéro de téléphone principal (WhatsApp)
  WHATSAPP_PHONE: '+212634713170',
  // Numéro de téléphone formaté pour l'affichage
  WHATSAPP_PHONE_DISPLAY: '+212 634 71 31 70',
  // Numéro de téléphone pour les liens WhatsApp (sans +)
  WHATSAPP_PHONE_LINK: '212634713170',
  // Format de numéro de téléphone pour les placeholders
  PHONE_PLACEHOLDER: '+212 634 71 31 70',
  // Format de validation des numéros
  PHONE_FORMAT: '+212 ### ## ## ##',
  // Informations de l'entreprise
  COMPANY: {
    name: 'AfrikRaga Demo',
    address: 'Casablanca, Maroc',
    email: 'contact@demo.local',
    website: 'http://localhost:5173'
  },
  // Messages de contact
  MESSAGES: {
    whatsapp: {
      order: 'Bonjour ! J\'ai une question concernant ma commande',
      support: 'Bonjour ! J\'ai besoin d\'aide avec mon compte',
      general: 'Bonjour ! J\'aimerais avoir des informations'
    }
  }
};

// Fonction utilitaire pour générer un lien WhatsApp
export const generateWhatsAppLink = (message = '', phone = CONTACT_CONFIG.WHATSAPP_PHONE_LINK) => {
  const baseUrl = `https://wa.me/${phone}`;
  if (message) {
    return `${baseUrl}?text=${encodeURIComponent(message)}`;
  }
  return baseUrl;
};

// Fonction utilitaire pour formater un numéro de téléphone
export const formatPhoneNumber = (phone) => {
  if (!phone) return '';
  const cleaned = phone.replace(/\D/g, '');
  if (cleaned.startsWith('212')) {
    return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 8)} ${cleaned.slice(8, 10)} ${cleaned.slice(10, 12)}`;
  }
  return phone;
};

export default CONTACT_CONFIG;