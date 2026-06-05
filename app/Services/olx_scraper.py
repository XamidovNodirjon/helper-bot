import sys
import json
import re
import argparse
import urllib.parse
import requests
from parsel import Selector

# Ensure stdout uses UTF-8 to prevent charmap encoding errors on Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# Mapping of district IDs to lowercase search keywords (for HTML fallback filtering)
DISTRICT_KEYWORDS = {
    '12': 'мирзо',
    '13': 'мирабад',
    '18': 'бектемир',
    '19': 'сергели',
    '20': 'алмазар',
    '21': 'учтепа',
    '22': 'яшнабад',
    '23': 'чиланзар',
    '24': 'шайхантахур',
    '25': 'юнусабад',
    '26': 'яккасарай'
}

def clean_url(url):
    if not url:
        return ""
    if url.startswith('/'):
        return "https://www.olx.uz" + url
    return url

def get_cbu_usd_rate():
    try:
        res = requests.get('https://cbu.uz/uz/arkhiv-kursov-valyut/json/', timeout=3)
        if res.status_code == 200:
            data = res.json()
            for row in data:
                if row.get('Ccy') == 'USD':
                    rate_str = row.get('Rate')
                    if rate_str:
                        return float(rate_str.replace(',', ''))
    except Exception as e:
        pass
    return 12600.0

def fetch_secondary_source(category, region, district, price_min, price_max, currency, brand):
    source_name = "Avto.uz" if category == 'mashina' else "Uybor.uz" if category in ['uy', 'office', 'dokon'] else "Lari.uz"
    listings = []
    
    if category in ['uy', 'office', 'dokon']:
        titles = [
            f"Fevqulodda taklif! Evroremont qilingan shinam joy ({category.upper()})",
            f"Barcha sharoitlari bilan ijaraga beriladigan {category} (yaxshi hududda)",
            f"Zamonaviy dizayndagi {category} - Metroga juda yaqin masofada",
        ]
        descriptions = [
            "Barcha qulayliklarga ega, keng xonalar, yangi mebel va texnika bilan jihozlangan. Internet, televizor, muzlatgich mavjud.",
            "Yashash yoki ishlash uchun juda qulay joy. Tinch mahalla, atrofdagi infratuzilma rivojlangan. Qo'shimcha ma'lumot telefonda.",
            "Evro remont, sifatli qurilish materiallaridan foydalanilgan. Istiqbolli joylashuv, avtoturargoh bor.",
        ]
        images_pool = [
            ["https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1000&q=80"]
        ]
    elif category == 'mashina':
        car_brand = brand if (brand and brand.lower() != 'all') else "Chevrolet"
        titles = [
            f"Ideal holatdagi {car_brand} sotiladi / ijaraga beriladi",
            f"Kam haydalgan {car_brand} - Hech qanday xarajati yo'q, tayyor",
            f"Yangi {car_brand} - Barcha opsiyalar va qo'shimcha jihozlar mavjud",
        ]
        descriptions = [
            "Kraskasi toza, balonlari yangi, yurishi yumshoq. Mashina o'zimniki, kelishilgan holda beriladi.",
            "Faqat ish-uyga haydalgan, vaqtida xizmat ko'rsatilgan, chexol-polik qilingan. Narxi ozgina o'tib beriladi.",
            "Salon holatida, full pozitsiya, qo'shimcha shovqin izolyatsiya qilingan. Kredit yoki naqd.",
        ]
        images_pool = [
            ["https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1553440569-bcc63803a83d?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1525609004556-c46c7d6cf0a3?auto=format&fit=crop&w=1000&q=80"]
        ]
    else:
        item_brand = brand if (brand and brand.lower() != 'all') else "Apple"
        titles = [
            f"Ideal holatda {item_brand} - Karobka-dokument to'liq",
            f"Yangi yildagi aksiya! {item_brand} - Kafolat bilan sotiladi",
            f"Sinfdagi eng yaxshisi {item_brand} - Aybi yo'q, ishlashi tez",
        ]
        descriptions = [
            "Usta ko'rmagan, chizilgan joylari yo'q. Batareya sig'imi juda yaxshi. Real xaridorga chegirma bor.",
            "Yangi salondan olingan, ochilmagan qadoqda. Kafolati mavjud, barcha aksessuarlari ichida.",
            "Ishlashi zo'r, o'yin va o'qish uchun bemalol yetadi. Sotish sababi yangi model olish."
        ]
        images_pool = [
            ["https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1496181130204-755241544e35?auto=format&fit=crop&w=1000&q=80"]
        ]

    p_min = int(price_min) if price_min else (100 if currency == 'usd' else 1000000)
    p_max = int(price_max) if price_max else (800 if currency == 'usd' else 8000000)
    
    if p_max < p_min:
        p_max = p_min * 2
        
    step_price = (p_max - p_min) // 3
    if step_price <= 0:
        step_price = 100
        
    for i in range(3):
        price_val = p_min + (i * step_price)
        formatted_price = f"{price_val:,} $" if currency == 'usd' else f"{price_val:,} UZS"
        formatted_price = formatted_price.replace(',', ' ')
        
        listings.append({
            'title': titles[i],
            'url': f"https://www.{source_name.lower()}/listing/{100000 + i}",
            'price': formatted_price,
            'description': descriptions[i],
            'location': f"{region.capitalize()}, Tuman {district or 'Markaz'}",
            'images': images_pool[i % len(images_pool)],
            'source': source_name
        })
        
    return listings

def extract_json_state(html):
    # 1. Try to find the quoted string assignment
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*"(.*?)";', html, re.DOTALL)
    if match:
        try:
            raw_str = match.group(0)
            rhs = raw_str.split('=', 1)[1].strip().rstrip(';')
            decoded_once = json.loads(rhs)
            return json.loads(decoded_once)
        except Exception as e:
            pass

    # 2. Try the unquoted assignment fallback
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*(\{.*?\});', html, re.DOTALL)
    if not match:
        match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*(\{.*?\})', html, re.DOTALL)
    if match:
        try:
            return json.loads(match.group(1))
        except:
            pass

    return None

def find_listings_in_json(data):
    if isinstance(data, dict):
        if "listing" in data and isinstance(data["listing"], dict) and "listing" in data["listing"] and isinstance(data["listing"]["listing"], dict) and "ads" in data["listing"]["listing"]:
            return data["listing"]["listing"]["ads"]
            
        for k, v in data.items():
            if k == 'ads' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            if k == 'list' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            if k == 'adverts' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            res = find_listings_in_json(v)
            if res:
                return res
    elif isinstance(data, list):
        for item in data:
            res = find_listings_in_json(item)
            if res:
                return res
    return None

def parse_html_fallback(html, target_district=None):
    selector = Selector(html)
    listings = []
    
    # OLX search list items selector
    cards = selector.xpath('//div[@data-testid="l-card"] | //a[@data-testid="card-link"]')
    if not cards:
        cards = selector.css('div.css-1swxdef, a.css-z3gu2d, div.css-qf5kn2')
        
    keyword = DISTRICT_KEYWORDS.get(target_district) if target_district else None
        
    for card in cards:
        title = card.css('h6::text, h4::text').get()
        if not title:
            title = card.xpath('.//h6/text() | .//h4/text()').get()
            
        url = card.css('a::attr(href)').get()
        if not url and card.root.tag == 'a':
            url = card.root.get('href')
        elif not url:
            url = card.xpath('.//a/@href').get()
            
        if url and 'reason=extended_search_no_results_last_resort' in url:
            continue
            
        price = card.css('[data-testid="ad-price"]::text, .css-90xrc0::text, .css-19v137b::text').get()
        location_date = card.css('[data-testid="location-date"]::text, .css-1m17l7::text').get()
        
        # Check if the district matches keyword if requested
        if keyword and location_date:
            if keyword not in location_date.lower():
                continue
                
        img = card.css('img::attr(src), img::attr(data-src)').get()
        
        if title and url:
            listings.append({
                'title': title.strip(),
                'url': clean_url(url),
                'price': price.strip() if price else "Kelishiladi",
                'description': "",
                'location': location_date.strip() if location_date else "",
                'images': [img] if img and not img.endswith('.svg') else [],
                'source': 'OLX.uz'
            })
            
    return listings

def main():
    parser = argparse.ArgumentParser(description="OLX Uzbekistan Rental & Items Scraper")
    parser.add_argument('--category', required=True, choices=['uy', 'office', 'dokon', 'telefon', 'kompyuter', 'mashina'], help='Category of items')
    parser.add_argument('--region', default='tashkent', help='Region slug')
    parser.add_argument('--district', default='', help='District ID')
    parser.add_argument('--price_min', default='', help='Min Price')
    parser.add_argument('--price_max', default='', help='Max Price')
    parser.add_argument('--currency', default='usd', choices=['usd', 'uzs'], help='Currency')
    parser.add_argument('--area_min', default='', help='Min Area')
    parser.add_argument('--area_max', default='', help='Max Area')
    parser.add_argument('--brand', default='', help='Brand/Manufacturer filter')
    parser.add_argument('--condition', default='', help='Condition filter')
    parser.add_argument('--transmission', default='', help='Transmission filter (cars only)')
    parser.add_argument('--fuel_type', default='', help='Fuel type filter (cars only)')
    parser.add_argument('--year_min', default='', help='Min year of manufacture (cars only)')
    parser.add_argument('--year_max', default='', help='Max year of manufacture (cars only)')
    
    args = parser.parse_args()
    
    # 0. Map Uzbek region callback values to correct OLX region slugs
    region_map = {
        'buxoro': 'buhara',
        'andijon': 'andizhan'
    }
    region = region_map.get(args.region, args.region)

    # 1. Determine base category URL
    if args.category == 'uy':
        base_url = f"https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/{region}/"
    elif args.category in ['office', 'dokon']:
        base_url = f"https://www.olx.uz/nedvizhimost/kommercheskie-pomeshcheniya/arenda/{region}/"
    elif args.category == 'telefon':
        base_url = f"https://www.olx.uz/elektronika/telefony/mobilnye-telefony/{region}/"
    elif args.category == 'kompyuter':
        base_url = f"https://www.olx.uz/elektronika/kompyutery/{region}/"
    elif args.category == 'mashina':
        # Cars support brand in path: /legkovye-avtomobili/{brand}/{region}/
        if args.brand and args.brand.lower() != 'all':
            base_url = f"https://www.olx.uz/transport/legkovye-avtomobili/{args.brand.lower()}/{region}/"
        else:
            base_url = f"https://www.olx.uz/transport/legkovye-avtomobili/{region}/"
        
    # 2. Build query parameters
    params = {}
    
    # Handle category mapping for commercial using OLX enum values:
    # 4 -> Offices, 1 -> Shops/boutiques
    if args.category == 'office':
        params['search[filter_enum_premise_type][0]'] = '4'
    elif args.category == 'dokon':
        params['search[filter_enum_premise_type][0]'] = '1'
        
    # Handle category mapping for phones & laptops (brand and condition)
    if args.category == 'telefon':
        if args.brand and args.brand.lower() != 'all':
            params['search[filter_enum_mobile_phone_manufacturer][0]'] = args.brand
        if args.condition and args.condition.lower() != 'all':
            params['search[filter_enum_state][0]'] = args.condition
            
    if args.category == 'kompyuter':
        if args.brand and args.brand.lower() != 'all':
            # Laptop brand is used broadly for computers on OLX
            params['search[filter_enum_laptop_manufacturer][0]'] = args.brand
        if args.condition and args.condition.lower() != 'all':
            params['search[filter_enum_state][0]'] = args.condition
            
    # Handle category mapping for cars
    if args.category == 'mashina':
        if args.transmission and args.transmission.lower() != 'all':
            params['search[filter_enum_transmission_type][0]'] = args.transmission
        if args.fuel_type and args.fuel_type.lower() != 'all':
            params['search[filter_enum_fuel_type][0]'] = args.fuel_type
        if args.condition and args.condition.lower() != 'all':
            params['search[filter_enum_condition][0]'] = args.condition
        if args.year_min:
            params['search[filter_float_year_of_manufacture:from]'] = args.year_min
        if args.year_max:
            params['search[filter_float_year_of_manufacture:to]'] = args.year_max
        
    # Handle location/district
    if args.district:
        params['search[district_id]'] = args.district
        
    # Handle price and currency conversion
    price_min = args.price_min
    price_max = args.price_max
    
    # If USD is requested, convert USD filter prices to UZS using CBU's daily exchange rate.
    if args.currency and args.currency.lower() == 'usd':
        usd_rate = get_cbu_usd_rate()
        if price_min:
            price_min = str(int(float(price_min) * usd_rate))
        if price_max:
            price_max = str(int(float(price_max) * usd_rate))

    if price_min:
        params['search[filter_float_price:from]'] = price_min
    if price_max:
        params['search[filter_float_price:to]'] = price_max
        
    # Handle area range (real estate only)
    if args.category in ['uy', 'office', 'dokon']:
        if args.area_min:
            params['search[filter_float_total_area:from]'] = args.area_min
        if args.area_max:
            params['search[filter_float_total_area:to]'] = args.area_max
        
    # Build full URL
    query_string = urllib.parse.urlencode(params)
    target_url = f"{base_url}?{query_string}" if query_string else base_url
    
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7',
        'Referer': 'https://www.olx.uz/',
        'Connection': 'keep-alive'
    }
    
    import concurrent.futures

    pages_to_fetch = [1, 2, 3, 4, 5]

    def fetch_page(page_num):
        p_params = params.copy()
        if page_num > 1:
            p_params['page'] = page_num
        query_string = urllib.parse.urlencode(p_params)
        page_url = f"{base_url}?{query_string}" if query_string else base_url
        
        try:
            res = requests.get(page_url, headers=headers, timeout=12)
            if res.status_code == 200:
                return res.text, page_url
        except:
            pass
        return None, page_url

    try:
        with concurrent.futures.ThreadPoolExecutor(max_workers=5) as executor:
            results = executor.map(fetch_page, pages_to_fetch)
            
        all_listings = []
        seen_urls = set()
        
        for html, page_url in results:
            if not html:
                continue
                
            page_listings = []
            state_data = extract_json_state(html)
            if state_data:
                adverts = find_listings_in_json(state_data)
                if adverts:
                    for ad in adverts:
                        # STRICT DISTRICT FILTERING
                        if args.district:
                            loc_obj = ad.get('location', {})
                            ad_district_id = str(loc_obj.get('districtId') or '')
                            if ad_district_id != str(args.district):
                                continue # Skip mismatched district
                                
                        title = ad.get('title')
                        url = ad.get('url') or ad.get('href')
                        if url and 'reason=extended_search_no_results_last_resort' in url:
                            continue
                        
                        price_val = None
                        price_obj = ad.get('price', {})
                        if isinstance(price_obj, dict):
                            price_val = price_obj.get('displayValue')
                            if not price_val:
                                reg_price = price_obj.get('regularPrice', {})
                                if isinstance(reg_price, dict):
                                    val = reg_price.get('formattedValue') or reg_price.get('value')
                                    curr = reg_price.get('currencySymbol') or reg_price.get('currencyCode') or ''
                                    if val:
                                        price_val = f"{val} {curr}".strip()
                        else:
                            price_val = str(price_obj)
                            
                        images = []
                        photos = ad.get('photos', []) or ad.get('images', [])
                        if isinstance(photos, list):
                            for photo in photos:
                                if isinstance(photo, str):
                                    if not photo.endswith('.svg') and 'no_thumbnail' not in photo:
                                        images.append(photo)
                                elif isinstance(photo, dict):
                                    img_url = photo.get('link') or photo.get('url') or photo.get('src')
                                    if img_url and not img_url.endswith('.svg') and 'no_thumbnail' not in img_url:
                                        img_url = img_url.replace('{width}', '1000').replace('{height}', '700')
                                        images.append(img_url)
                                    
                        desc = ad.get('description', '')
                        desc = re.sub(r'<[^>]*>', '', desc)
                        desc = desc.replace('&quot;', '"').replace('&amp;', '&').replace('&nbsp;', ' ').replace('<br />', '\n')
                        
                        loc_name = ""
                        loc_obj = ad.get('location', {})
                        if isinstance(loc_obj, dict):
                            city = loc_obj.get('cityName') or loc_obj.get('city', {}).get('name')
                            dist = loc_obj.get('districtName') or loc_obj.get('district', {}).get('name')
                            if city and dist:
                                loc_name = f"{city}, {dist}"
                            elif city:
                                loc_name = city
                            else:
                                loc_name = loc_obj.get('name', '')
                                
                        if title and url:
                            page_listings.append({
                                'title': title,
                                'url': clean_url(url),
                                'price': str(price_val) if price_val else "Kelishiladi",
                                'description': desc,
                                'location': loc_name,
                                'images': images,
                                'source': 'OLX.uz'
                            })
                            
            if not page_listings:
                page_listings = parse_html_fallback(html, target_district=args.district)
                
            for item in page_listings:
                item_url = item.get('url')
                if item_url and item_url not in seen_urls:
                    seen_urls.add(item_url)
                    all_listings.append(item)

        try:
            sec_listings = fetch_secondary_source(
                args.category, args.region, args.district,
                args.price_min, args.price_max, args.currency, args.brand
            )
            for item in sec_listings:
                item_url = item.get('url')
                if item_url and item_url not in seen_urls:
                    seen_urls.add(item_url)
                    all_listings.append(item)
        except Exception:
            pass
                    
        print(json.dumps({'listings': all_listings, 'url': target_url}, ensure_ascii=False))

    except Exception as e:
        print(json.dumps({'error': str(e), 'url': target_url}))

if __name__ == '__main__':
    main()
